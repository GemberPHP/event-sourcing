<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository\Snapshot;

use Gember\DependencyContracts\Util\Serialization\Serializer\Serializer;
use Gember\EventSourcing\EventStore\EventStore;
use Gember\EventSourcing\EventStore\NoEventsForDomainTagsException;
use Gember\EventSourcing\EventStore\StreamQuery;
use Gember\EventSourcing\Repository\UseCaseNotFoundException;
use Gember\EventSourcing\Repository\UseCaseRepository;
use Gember\EventSourcing\Repository\UseCaseRepositoryFailedException;
use Gember\EventSourcing\Resolver\UseCase\UseCaseResolver;
use Gember\EventSourcing\Snapshot\Policy\SnapshotContext;
use Gember\EventSourcing\Snapshot\Policy\SnapshotPolicy;
use Psr\Log\LoggerInterface;
use Gember\EventSourcing\Snapshot\SnapshotEnvelope;
use Gember\EventSourcing\Snapshot\SnapshotStore;
use Gember\EventSourcing\Util\String\ClassNameSegmentHelper;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Override;
use Stringable;
use Throwable;
use WeakMap;

final class SnapshotUseCaseRepositoryDecorator implements UseCaseRepository
{
    /**
     * @var WeakMap<EventSourcedUseCase, SourcingContext>
     */
    private WeakMap $sourcingContexts;

    /**
     * @param iterable<SnapshotPolicy> $snapshotPolicies
     */
    public function __construct(
        private readonly UseCaseRepository $useCaseRepository,
        private readonly EventStore $eventStore,
        private readonly UseCaseResolver $useCaseResolver,
        private readonly SnapshotStore $snapshotStore,
        private readonly Serializer $serializer,
        private readonly iterable $snapshotPolicies,
        private readonly LoggerInterface $logger,
    ) {
        /** @var WeakMap<EventSourcedUseCase, SourcingContext> $sourcingContexts */
        $sourcingContexts = new WeakMap();
        $this->sourcingContexts = $sourcingContexts;
    }

    /**
     * @template T of EventSourcedUseCase
     *
     * @param class-string<T> $useCaseClassName
     *
     * @throws UseCaseNotFoundException
     * @throws UseCaseRepositoryFailedException
     *
     * @return T
     */
    #[Override]
    public function get(string $useCaseClassName, string|Stringable ...$domainTag): EventSourcedUseCase
    {
        $useCaseDefinition = $this->useCaseResolver->resolve($useCaseClassName);
        $snapshotDefinition = $useCaseDefinition->snapshotDefinition;

        if ($snapshotDefinition === null) {
            return $this->useCaseRepository->get($useCaseClassName, ...$domainTag);
        }

        $domainTags = array_values($domainTag);
        $domainTagStrings = array_map(strval(...), $domainTags);
        $eventClassNames = array_map(
            fn($eventSubscriberDefinition) => $eventSubscriberDefinition->eventClassName,
            $useCaseDefinition->eventSubscribers,
        );

        try {
            $snapshot = $this->snapshotStore->load($domainTagStrings, $eventClassNames);

            $startTime = microtime(true);

            if ($snapshot !== null) {
                $eventCountAtLastSnapshot = $snapshot->eventCount;
                $snapshotIsValid = true;

                try {
                    $eventEnvelopes = $this->eventStore->load(new StreamQuery(
                        $domainTags,
                        $eventClassNames,
                        $snapshot->lastEventId,
                    ));
                } catch (NoEventsForDomainTagsException) {
                    // No events after snapshot — either snapshot is current, or lastEventId is invalid.
                    // Verify by checking if any events exist at all for this boundary.
                    $eventEnvelopes = [];

                    try {
                        $allEnvelopes = $this->eventStore->load(new StreamQuery(
                            $domainTags,
                            $eventClassNames,
                        ));

                        // Events exist but afterEventId returned nothing — snapshot is stale
                        $this->logger->warning('[Snapshot] Stale snapshot detected, falling back to full replay', [
                            'lastEventId' => $snapshot->lastEventId,
                            'domainTags' => $domainTagStrings,
                        ]);

                        $snapshotIsValid = false;
                        $eventEnvelopes = $allEnvelopes;
                    } catch (NoEventsForDomainTagsException) {
                        // No events at all — snapshot is genuinely current (or use case was deleted)
                    }
                }

                if ($snapshotIsValid) {
                    $useCase = $useCaseClassName::reconstituteFromSnapshot(
                        $this->serializer->deserialize($snapshot->state, $useCaseClassName),
                        ...$eventEnvelopes,
                    );

                    $eventCount = $snapshot->eventCount + count($eventEnvelopes);
                } else {
                    $eventCountAtLastSnapshot = 0;
                    $useCase = $useCaseClassName::reconstitute(...$eventEnvelopes);
                    $eventCount = count($eventEnvelopes);
                }
            } else {
                $eventCountAtLastSnapshot = 0;

                $eventEnvelopes = $this->eventStore->load(new StreamQuery(
                    $domainTags,
                    $eventClassNames,
                ));

                $useCase = $useCaseClassName::reconstitute(...$eventEnvelopes);

                $eventCount = count($eventEnvelopes);
            }

            $sourcingDurationMs = (microtime(true) - $startTime) * 1000;

            $this->sourcingContexts[$useCase] = new SourcingContext(
                $sourcingDurationMs,
                $eventCount,
                $eventCountAtLastSnapshot,
                $snapshotDefinition,
                $domainTagStrings,
                $eventClassNames,
            );

            return $useCase;
        } catch (NoEventsForDomainTagsException) {
            throw UseCaseNotFoundException::create();
        } catch (Throwable $exception) {
            throw UseCaseRepositoryFailedException::withException($exception);
        }
    }

    #[Override]
    public function has(string $useCaseClassName, string|Stringable ...$domainTag): bool
    {
        return $this->useCaseRepository->has($useCaseClassName, ...$domainTag);
    }

    /**
     * The inner repository's save() is expected to call $useCase->setLastEventId()
     * with the last persisted event ID. The snapshot uses this to record the correct
     * position in the event stream.
     */
    #[Override]
    public function save(EventSourcedUseCase $useCase): void
    {
        $appliedEvents = $useCase->getAppliedEvents();

        $this->useCaseRepository->save($useCase);

        $this->createSnapshotIfNeeded($useCase, $appliedEvents);
    }

    /**
     * @param list<object> $appliedEvents
     */
    private function createSnapshotIfNeeded(EventSourcedUseCase $useCase, array $appliedEvents): void
    {
        $sourcingContext = $this->sourcingContexts[$useCase] ?? null;

        if ($sourcingContext === null) {
            return;
        }

        $totalEventCount = $sourcingContext->eventCount + count($appliedEvents);

        $snapshotContext = new SnapshotContext(
            $sourcingContext->sourcingDurationMs,
            $totalEventCount,
            $sourcingContext->eventCountAtLastSnapshot,
            $appliedEvents,
        );

        $lastEventId = $useCase->getLastEventId();

        if ($lastEventId === null) {
            return;
        }

        foreach ($this->snapshotPolicies as $policy) {
            if ($policy->shouldSnapshot($sourcingContext->snapshotDefinition, $snapshotContext)) {
                $this->logger->info('[Snapshot] Snapshot triggered', [
                    'policy' => ClassNameSegmentHelper::getLastSegment($policy::class),
                    'domainTags' => $sourcingContext->domainTags,
                    'eventCount' => $totalEventCount,
                ]);

                try {
                    $this->snapshotStore->save(new SnapshotEnvelope(
                        $sourcingContext->domainTags,
                        $sourcingContext->eventNames,
                        $lastEventId,
                        $totalEventCount,
                        $this->serializer->serialize($useCase),
                    ));
                } catch (Throwable $exception) {
                    $this->logger->warning('[Snapshot] Failed saving snapshot, skipping', [
                        'exception' => $exception->getMessage(),
                        'exceptionClass' => $exception::class,
                        'domainTags' => $sourcingContext->domainTags,
                    ]);
                }

                unset($this->sourcingContexts[$useCase]);

                return;
            }
        }
    }
}

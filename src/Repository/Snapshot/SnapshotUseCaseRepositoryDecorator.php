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
            $startTime = microtime(true);

            $result = $this->reconstituteWithSnapshot($useCaseClassName, $domainTags, $domainTagStrings, $eventClassNames);

            $sourcingDurationMs = (microtime(true) - $startTime) * 1000;

            $this->sourcingContexts[$result->useCase] = new SourcingContext(
                $sourcingDurationMs,
                $result->eventCount,
                $result->eventCountAtLastSnapshot,
                $snapshotDefinition,
                $domainTagStrings,
                $eventClassNames,
            );

            return $result->useCase;
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
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     * @param list<string|Stringable> $domainTags
     * @param list<string> $domainTagStrings
     * @param list<class-string> $eventClassNames
     */
    private function reconstituteWithSnapshot(
        string $useCaseClassName,
        array $domainTags,
        array $domainTagStrings,
        array $eventClassNames,
    ): ReconstitutionResult {
        try {
            $snapshot = $this->snapshotStore->load($domainTagStrings, $eventClassNames);
        } catch (Throwable $exception) {
            $this->logger->warning('[Snapshot] Failed loading snapshot, falling back to full replay', [
                'exception' => $exception->getMessage(),
                'exceptionClass' => $exception::class,
                'domainTags' => $domainTagStrings,
            ]);
            $snapshot = null;
        }

        if ($snapshot !== null) {
            return $this->reconstituteFromSnapshot($useCaseClassName, $snapshot, $domainTags, $domainTagStrings, $eventClassNames);
        }

        return $this->reconstituteFromEvents($useCaseClassName, $domainTags, $eventClassNames);
    }

    /**
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     * @param list<string|Stringable> $domainTags
     * @param list<string> $domainTagStrings
     * @param list<class-string> $eventClassNames
     */
    private function reconstituteFromSnapshot(
        string $useCaseClassName,
        SnapshotEnvelope $snapshot,
        array $domainTags,
        array $domainTagStrings,
        array $eventClassNames,
    ): ReconstitutionResult {
        try {
            $eventEnvelopes = $this->eventStore->load(new StreamQuery(
                $domainTags,
                $eventClassNames,
                $snapshot->lastEventId,
            ));
        } catch (NoEventsForDomainTagsException) {
            return $this->handleStaleLastEventId($useCaseClassName, $snapshot, $domainTags, $domainTagStrings, $eventClassNames);
        }

        /*
         * Events after the snapshot are loaded by applied_at order, which doesn't follow commit order: an event
         * committed after the snapshot with an earlier applied_at would be skipped. Verify the snapshot plus the
         * loaded events account for all events. The count is read after loading, so a mismatch can only mean
         * missing (or double counted) events, or new events committed in between: replay to be safe.
         */
        $eventCount = $snapshot->eventCount + count($eventEnvelopes);

        if ($this->eventStore->count(new StreamQuery($domainTags, $eventClassNames)) !== $eventCount) {
            $this->logger->info('[Snapshot] Snapshot does not account for all events, falling back to full replay', [
                'lastEventId' => $snapshot->lastEventId,
                'domainTags' => $domainTagStrings,
            ]);

            return $this->reconstituteFromEvents($useCaseClassName, $domainTags, $eventClassNames);
        }

        try {
            $deserialized = $this->serializer->deserialize($snapshot->state, $useCaseClassName);
        } catch (Throwable $exception) {
            $this->logger->info('[Snapshot] Stale snapshot, falling back to full replay', [
                'exception' => $exception->getMessage(),
                'exceptionClass' => $exception::class,
                'domainTags' => $domainTagStrings,
            ]);

            return $this->reconstituteFromEvents($useCaseClassName, $domainTags, $eventClassNames);
        }

        $useCase = $useCaseClassName::reconstituteFromSnapshot($deserialized, ...$eventEnvelopes);
        $useCase->setEventCount($eventCount);

        return new ReconstitutionResult(
            $useCase,
            $eventCount,
            $snapshot->eventCount,
        );
    }

    /**
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     * @param list<string|Stringable> $domainTags
     * @param list<string> $domainTagStrings
     * @param list<class-string> $eventClassNames
     */
    private function handleStaleLastEventId(
        string $useCaseClassName,
        SnapshotEnvelope $snapshot,
        array $domainTags,
        array $domainTagStrings,
        array $eventClassNames,
    ): ReconstitutionResult {
        // No events after snapshot — the snapshot is current if it accounts for all events
        if ($this->eventStore->count(new StreamQuery($domainTags, $eventClassNames)) === $snapshot->eventCount) {
            $deserialized = $this->serializer->deserialize($snapshot->state, $useCaseClassName);

            $useCase = $useCaseClassName::reconstituteFromSnapshot($deserialized);
            $useCase->setEventCount($snapshot->eventCount);

            return new ReconstitutionResult(
                $useCase,
                $snapshot->eventCount,
                $snapshot->eventCount,
            );
        }

        // Events exist that the snapshot doesn't account for — snapshot is stale
        $this->logger->info('[Snapshot] Stale snapshot detected, falling back to full replay', [
            'lastEventId' => $snapshot->lastEventId,
            'domainTags' => $domainTagStrings,
        ]);

        return $this->reconstituteFromEvents($useCaseClassName, $domainTags, $eventClassNames);
    }

    /**
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     * @param list<string|Stringable> $domainTags
     * @param list<class-string> $eventClassNames
     */
    private function reconstituteFromEvents(
        string $useCaseClassName,
        array $domainTags,
        array $eventClassNames,
    ): ReconstitutionResult {
        $eventEnvelopes = $this->eventStore->load(new StreamQuery(
            $domainTags,
            $eventClassNames,
        ));

        return new ReconstitutionResult(
            $useCaseClassName::reconstitute(...$eventEnvelopes),
            count($eventEnvelopes),
            0,
        );
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

        // Number of events matching the use case's stream, including its own appended events that match
        $totalEventCount = $useCase->getEventCount();

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

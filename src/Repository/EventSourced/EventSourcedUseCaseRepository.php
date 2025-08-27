<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository\EventSourced;

use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\EventStore\DomainEventEnvelopeFactory;
use Gember\EventSourcing\EventStore\EventStore;
use Gember\EventSourcing\EventStore\NoEventsForDomainTagsException;
use Gember\EventSourcing\EventStore\StreamQuery;
use Gember\EventSourcing\Repository\UseCaseNotFoundException;
use Gember\EventSourcing\Repository\UseCaseRepository;
use Gember\EventSourcing\Repository\UseCaseRepositoryFailedException;
use Gember\EventSourcing\Resolver\UseCase\SubscribedEvents\SubscribedEventsResolver;
use Gember\EventSourcing\Util\Messaging\MessageBus\EventBus;
use Override;
use Stringable;
use Throwable;

final readonly class EventSourcedUseCaseRepository implements UseCaseRepository
{
    public function __construct(
        private EventStore $eventStore,
        private DomainEventEnvelopeFactory $domainEventEnvelopeFactory,
        private SubscribedEventsResolver $subscribedEventsResolver,
        private EventBus $eventBus,
    ) {}

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
        try {
            $eventEnvelopes = $this->eventStore->load(new StreamQuery(
                array_values($domainTag),
                $this->subscribedEventsResolver->resolve($useCaseClassName),
            ));

            return $useCaseClassName::reconstitute(...$eventEnvelopes); // @phpstan-ignore-line
        } catch (NoEventsForDomainTagsException) {
            throw UseCaseNotFoundException::create();
        } catch (Throwable $exception) {
            throw UseCaseRepositoryFailedException::withException($exception);
        }
    }

    /**
     * @template T of EventSourcedUseCase
     *
     * @param class-string<T> $useCaseClassName
     *
     * @throws UseCaseRepositoryFailedException
     */
    #[Override]
    public function has(string $useCaseClassName, string|Stringable ...$domainTag): bool
    {
        try {
            $this->get($useCaseClassName, ...$domainTag);
        } catch (UseCaseNotFoundException) {
            return false;
        }

        return true;
    }

    #[Override]
    public function save(EventSourcedUseCase $useCase): void
    {
        $appliedEvents = $useCase->getAppliedEvents();

        try {
            $eventEnvelopes = array_map(
                fn($appliedEvent) => $this->domainEventEnvelopeFactory->createFromAppliedEvent($appliedEvent),
                $appliedEvents,
            );

            $this->eventStore->append(
                new StreamQuery(
                    $useCase->getDomainTags(),
                    $this->subscribedEventsResolver->resolve($useCase::class),
                ),
                $useCase->getLastEventId(),
                ...$eventEnvelopes,
            );
        } catch (Throwable $exception) {
            throw UseCaseRepositoryFailedException::withException($exception);
        }

        // todo: make event bus + event store atomic
        foreach ($appliedEvents as $appliedEvent) {
            $this->eventBus->handle($appliedEvent);
        }
    }
}

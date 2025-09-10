<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore\Rdbms;

use Gember\DependencyContracts\EventStore\Rdbms\RdbmsEventStoreRepository;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventResolver;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\EventStore\EventStore;
use Gember\EventSourcing\EventStore\EventStoreFailedException;
use Gember\EventSourcing\EventStore\NoEventsForDomainTagsException;
use Gember\EventSourcing\EventStore\OptimisticLockException;
use Gember\EventSourcing\EventStore\StreamQuery;
use Throwable;
use Override;

final readonly class RdbmsEventStore implements EventStore
{
    public function __construct(
        private DomainEventResolver $domainEventResolver,
        private RdbmsDomainEventEnvelopeFactory $domainEventEnvelopeFactory,
        private RdbmsEventFactory $rdbmsEventFactory,
        private RdbmsEventStoreRepository $repository,
    ) {}

    #[Override]
    public function load(StreamQuery $streamQuery): array
    {
        try {
            $rdbmsEvents = $this->repository->getEvents(
                $streamQuery->domainTags,
                $this->getEventNamesFromStreamQuery($streamQuery),
            );

            $eventEnvelopes = array_map(
                fn($rdbmsEvent) => $this->domainEventEnvelopeFactory->createFromRdbmsEvent($rdbmsEvent),
                $rdbmsEvents,
            );
        } catch (Throwable $exception) {
            throw EventStoreFailedException::withException($exception);
        }

        if ($eventEnvelopes === []) {
            throw NoEventsForDomainTagsException::create();
        }

        return $eventEnvelopes;
    }

    #[Override]
    public function append(StreamQuery $streamQuery, ?string $lastEventId, DomainEventEnvelope ...$eventEnvelopes): void
    {
        $envelopes = array_values($eventEnvelopes);

        if ($envelopes === []) {
            return;
        }

        try {
            $this->guardOptimisticLock($streamQuery, $lastEventId);
        } catch (OptimisticLockException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw EventStoreFailedException::withException($exception);
        }

        try {
            $this->repository->saveEvents(array_map(
                fn($envelope) => $this->rdbmsEventFactory->createFromDomainEventEnvelope($envelope),
                $envelopes,
            ));
        } catch (Throwable $exception) {
            throw EventStoreFailedException::withException($exception);
        }
    }

    /**
     * @throws OptimisticLockException
     */
    private function guardOptimisticLock(StreamQuery $streamQuery, ?string $lastEventId): void
    {
        $lastEventIdPersisted = $this->repository->getLastEventIdPersisted(
            $streamQuery->domainTags,
            $this->getEventNamesFromStreamQuery($streamQuery),
        );

        if ($lastEventIdPersisted === null) {
            return;
        }

        if ($lastEventIdPersisted !== $lastEventId) {
            throw OptimisticLockException::create();
        }
    }

    /**
     * @return list<string>
     */
    private function getEventNamesFromStreamQuery(StreamQuery $streamQuery): array
    {
        return array_map(
            fn($eventClassName) => $this->domainEventResolver->resolve($eventClassName)->eventName,
            $streamQuery->eventClassNames,
        );
    }
}

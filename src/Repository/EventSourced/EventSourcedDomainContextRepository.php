<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository\EventSourced;

use Gember\EventSourcing\DomainContext\EventSourcedDomainContext;
use Gember\EventSourcing\EventStore\DomainEventEnvelopeFactory;
use Gember\EventSourcing\EventStore\EventStore;
use Gember\EventSourcing\EventStore\NoEventsForDomainIdsException;
use Gember\EventSourcing\EventStore\StreamQuery;
use Gember\EventSourcing\Repository\DomainContextNotFoundException;
use Gember\EventSourcing\Repository\DomainContextRepository;
use Gember\EventSourcing\Repository\DomainContextRepositoryFailedException;
use Gember\EventSourcing\Resolver\DomainContext\SubscribedEvents\SubscribedEventsResolver;
use Gember\EventSourcing\Util\Messaging\MessageBus\EventBus;
use Override;
use Stringable;
use Throwable;

/**
 * @template T of EventSourcedDomainContext
 *
 * @implements DomainContextRepository<EventSourcedDomainContext<T>>
 */
final readonly class EventSourcedDomainContextRepository implements DomainContextRepository
{
    /**
     * @param SubscribedEventsResolver<T> $eventsInDomainContextResolver
     */
    public function __construct(
        private EventStore $eventStore,
        private DomainEventEnvelopeFactory $domainEventEnvelopeFactory,
        private SubscribedEventsResolver $eventsInDomainContextResolver,
        private EventBus $eventBus,
    ) {}

    #[Override]
    public function get(string $domainContextClassName, string|Stringable ...$domainId): EventSourcedDomainContext
    {
        try {
            $eventEnvelopes = $this->eventStore->load(new StreamQuery(
                array_values($domainId),
                $this->eventsInDomainContextResolver->resolve($domainContextClassName),
            ));

            return $domainContextClassName::reconstitute(...$eventEnvelopes);
        } catch (NoEventsForDomainIdsException) {
            throw DomainContextNotFoundException::create();
        } catch (Throwable $exception) {
            throw DomainContextRepositoryFailedException::withException($exception);
        }
    }

    #[Override]
    public function has(string $domainContextClassName, string|Stringable ...$domainId): bool
    {
        try {
            $this->get($domainContextClassName, ...$domainId);
        } catch (DomainContextNotFoundException) {
            return false;
        }

        return true;
    }

    #[Override]
    public function save(EventSourcedDomainContext $context): void
    {
        $appliedEvents = $context->getAppliedEvents();

        try {
            $eventEnvelopes = array_map(
                fn($appliedEvent) => $this->domainEventEnvelopeFactory->createFromAppliedEvent($appliedEvent),
                $appliedEvents,
            );

            $this->eventStore->append(
                new StreamQuery(
                    $context->getDomainIds(),
                    $this->eventsInDomainContextResolver->resolve($context::class),
                ),
                $context->getLastEventId(),
                ...$eventEnvelopes,
            );
        } catch (Throwable $exception) {
            throw DomainContextRepositoryFailedException::withException($exception);
        }

        // todo: make event bus + event store atomic
        foreach ($appliedEvents as $appliedEvent) {
            $this->eventBus->handle($appliedEvent);
        }
    }
}

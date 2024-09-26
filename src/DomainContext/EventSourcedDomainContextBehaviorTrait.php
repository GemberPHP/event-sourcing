<?php

declare(strict_types=1);

namespace Gember\EventSourcing\DomainContext;

/**
 * @template T of EventSourcedDomainContext
 *
 * @phpstan-require-implements EventSourcedDomainContext
 */
trait EventSourcedDomainContextBehaviorTrait
{
    private ?string $lastEventId = null;

    /**
     * @var list<object>
     */
    private array $appliedEvents = [];

    public function apply(object $event): void
    {
        $this->appliedEvents[] = $event;
        $this->applyEventInDomainContext($event);
    }

    public function getDomainIds(): array
    {
        return array_map(
            fn($property) => $this->{$property},
            DomainContextAttributeRegistry::getDomainIdPropertiesForContext($this::class),
        );
    }

    public function getLastEventId(): ?string
    {
        return $this->lastEventId;
    }

    public function getAppliedEvents(): array
    {
        $appliedEvents = $this->appliedEvents;

        $this->appliedEvents = [];

        return $appliedEvents;
    }

    /**
     * @return T
     */
    public static function reconstitute(DomainEventEnvelope ...$envelopes): EventSourcedDomainContext
    {
        $domainContext = new self();

        foreach ($envelopes as $envelope) {
            $domainContext->applyEventInDomainContext($envelope->event);
            $domainContext->lastEventId = $envelope->eventId;
        }

        return $domainContext;
    }

    private function applyEventInDomainContext(object $event): void
    {
        $method = DomainContextAttributeRegistry::getContextSubscriberMethodForEvent($this::class, $event::class);

        if ($method !== null) {
            $this->{$method}($event);
        }
    }
}

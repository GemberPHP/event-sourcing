<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase;

/**
 * @phpstan-require-implements EventSourcedUseCase
 */
trait EventSourcedUseCaseBehaviorTrait
{
    private ?string $lastEventId = null;

    /**
     * @var list<object>
     */
    private array $appliedEvents = [];

    public function apply(object $event): void
    {
        $this->appliedEvents[] = $event;
        $this->applyEventInUseCase($event);
    }

    public function getDomainTags(): array
    {
        return array_map(
            fn($property) => $this->{$property},
            UseCaseAttributeRegistry::getDomainTagPropertiesForUseCase($this::class),
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

    public static function reconstitute(DomainEventEnvelope ...$envelopes): self
    {
        $useCase = new self();

        foreach ($envelopes as $envelope) {
            $useCase->applyEventInUseCase($envelope->event);
            $useCase->lastEventId = $envelope->eventId;
        }

        return $useCase;
    }

    private function applyEventInUseCase(object $event): void
    {
        $method = UseCaseAttributeRegistry::getUseCaseSubscriberMethodForEvent($this::class, $event::class);

        if ($method !== null) {
            $this->{$method}($event);
        }
    }
}

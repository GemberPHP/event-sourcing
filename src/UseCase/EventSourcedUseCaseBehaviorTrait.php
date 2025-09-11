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
            fn($domainTagDefinition) => $this->{$domainTagDefinition->domainTagName},
            UseCaseAttributeRegistry::getUseCaseDefinition($this::class)->domainTags,
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
        $useCaseDefinition = UseCaseAttributeRegistry::getUseCaseDefinition($this::class);
        foreach ($useCaseDefinition->eventSubscribers as $eventSubscriber) {
            if ($eventSubscriber->eventClassName !== $event::class) {
                continue;
            }

            $this->{$eventSubscriber->methodName}($event);
        }
    }
}

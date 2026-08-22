<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase;

use Stringable;

interface EventSourcedUseCase
{
    /**
     * @return list<string|Stringable>
     */
    public function getDomainTags(): array;

    public function getLastEventId(): ?string;

    public function setLastEventId(string $lastEventId): void;

    /**
     * @return list<object>
     */
    public function getAppliedEvents(): array;

    public function clearAppliedEvents(): void;

    public static function reconstitute(DomainEventEnvelope ...$envelopes): self;

    public static function reconstituteFromSnapshot(object $snapshotState, DomainEventEnvelope ...$envelopes): self;
}

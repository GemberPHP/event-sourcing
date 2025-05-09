<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase;

use Stringable;

interface EventSourcedUseCase
{
    /**
     * @return list<string|Stringable>
     */
    public function getDomainIds(): array;

    public function getLastEventId(): ?string;

    /**
     * @return list<object>
     */
    public function getAppliedEvents(): array;

    public static function reconstitute(DomainEventEnvelope ...$envelopes): self;
}

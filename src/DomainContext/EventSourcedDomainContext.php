<?php

declare(strict_types=1);

namespace Gember\EventSourcing\DomainContext;

use Stringable;

/**
 * @template-covariant T of EventSourcedDomainContext
 */
interface EventSourcedDomainContext
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

    /**
     * @return T
     */
    public static function reconstitute(DomainEventEnvelope ...$envelopes): EventSourcedDomainContext;
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase;

use Stringable;

interface SpecifiedDomainTagsDomainEvent
{
    /**
     * @return list<string|Stringable>
     */
    public function getDomainTags(): array;
}

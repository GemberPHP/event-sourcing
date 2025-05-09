<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase;

use Stringable;

interface SpecifiedDomainIdsDomainEvent
{
    /**
     * @return list<string|Stringable>
     */
    public function getDomainIds(): array;
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase;

use Stringable;

interface SpecifiedDomainTagsDomainMessage
{
    /**
     * @return list<string|Stringable>
     */
    public function getDomainTags(): array;
}

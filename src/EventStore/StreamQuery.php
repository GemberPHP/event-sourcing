<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore;

use Stringable;

final readonly class StreamQuery
{
    /**
     * @param list<string|Stringable> $domainIds
     * @param list<class-string> $eventClassNames
     */
    public function __construct(
        public array $domainIds,
        public array $eventClassNames = [],
    ) {}
}

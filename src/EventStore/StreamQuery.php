<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore;

use Stringable;

final readonly class StreamQuery
{
    /**
     * @param list<string|Stringable> $domainTags
     * @param list<class-string> $eventClassNames
     */
    public function __construct(
        public array $domainTags,
        public array $eventClassNames = [],
    ) {}
}

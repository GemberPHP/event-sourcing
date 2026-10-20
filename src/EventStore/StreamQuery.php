<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore;

use Gember\EventSourcing\UseCase\DomainEventEnvelope;
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
        public ?string $afterEventId = null,
    ) {}

    /**
     * Whether the event is part of this stream: one of its event classes, with at least one of its domain tags.
     */
    public function matches(DomainEventEnvelope $envelope): bool
    {
        if (!in_array($envelope->event::class, $this->eventClassNames, true)) {
            return false;
        }

        $domainTags = array_map(strval(...), $this->domainTags);

        return array_intersect($envelope->domainTags, $domainTags) !== [];
    }
}

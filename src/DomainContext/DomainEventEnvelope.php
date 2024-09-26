<?php

declare(strict_types=1);

namespace Gember\EventSourcing\DomainContext;

use DateTimeImmutable;

final readonly class DomainEventEnvelope
{
    /**
     * @param list<string> $domainIds
     */
    public function __construct(
        public string $eventId,
        public array $domainIds,
        public object $event,
        public Metadata $metadata,
        public DateTimeImmutable $appliedAt,
    ) {}
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore\Rdbms;

use DateTimeImmutable;

final readonly class RdbmsEvent
{
    /**
     * @param list<string> $domainTags
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $eventId,
        public array $domainTags,
        public string $eventName,
        public string $payload,
        public array $metadata,
        public DateTimeImmutable $appliedAt,
    ) {}

    public function withDomainTag(string $domainTag): self
    {
        return new self(
            $this->eventId,
            [...$this->domainTags, $domainTag],
            $this->eventName,
            $this->payload,
            $this->metadata,
            $this->appliedAt,
        );
    }
}

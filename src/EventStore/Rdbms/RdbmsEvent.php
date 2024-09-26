<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore\Rdbms;

use DateTimeImmutable;

final readonly class RdbmsEvent
{
    /**
     * @param list<string> $domainIds
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $eventId,
        public array $domainIds,
        public string $eventName,
        public string $payload,
        public array $metadata,
        public DateTimeImmutable $appliedAt,
    ) {}

    public function withDomainId(string $domainId): self
    {
        return new self(
            $this->eventId,
            [...$this->domainIds, $domainId],
            $this->eventName,
            $this->payload,
            $this->metadata,
            $this->appliedAt,
        );
    }
}

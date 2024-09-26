<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\DomainContext;

use Gember\EventSourcing\DomainContext\Attribute\DomainEvent;
use Gember\EventSourcing\DomainContext\Attribute\DomainId;

#[DomainEvent(name: 'test.domain-context.created')]
final readonly class TestDomainContextCreatedEvent
{
    public function __construct(
        #[DomainId]
        public string $id,
        #[DomainId]
        public string $secondaryId,
    ) {}
}

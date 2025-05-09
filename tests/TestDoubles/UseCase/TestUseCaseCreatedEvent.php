<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainId;

#[DomainEvent(name: 'test.use-case.created')]
final readonly class TestUseCaseCreatedEvent
{
    public function __construct(
        #[DomainId]
        public string $id,
        #[DomainId]
        public string $secondaryId,
    ) {}
}

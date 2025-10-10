<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;

#[DomainEvent(name: 'test.use-case.created')]
final readonly class TestUseCaseCreatedEvent
{
    public function __construct(
        #[DomainTag]
        #[SagaId]
        public string $id,
        #[DomainTag]
        public string $secondaryId,
    ) {}
}

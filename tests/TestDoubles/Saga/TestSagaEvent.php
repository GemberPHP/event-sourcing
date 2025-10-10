<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Saga;

use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\UseCase\Attribute\DomainEvent;

#[DomainEvent(name: 'test.saga.event')]
final readonly class TestSagaEvent
{
    public function __construct(
        #[SagaId(name: 'testSagaId')]
        public string $sagaId,
    ) {}
}

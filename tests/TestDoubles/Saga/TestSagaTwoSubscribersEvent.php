<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Saga;

use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\UseCase\Attribute\DomainEvent;

#[DomainEvent(name: 'test.saga.two-subscribers-event')]
final readonly class TestSagaTwoSubscribersEvent
{
    public function __construct(
        #[SagaId(name: 'twoSubscribersId')]
        public string $sagaId,
    ) {}
}

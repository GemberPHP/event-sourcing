<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Saga;

use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\UseCase\Attribute\DomainEvent;

#[DomainEvent(name: 'test.saga.two-ids-event')]
final readonly class TestSagaTwoIdsEvent
{
    public function __construct(
        #[SagaId(name: 'orderId')]
        public string $orderId,
        #[SagaId(name: 'paymentId')]
        public string $paymentId,
    ) {}
}

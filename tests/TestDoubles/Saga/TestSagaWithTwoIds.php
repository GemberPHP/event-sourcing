<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Saga;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Saga\Attribute\SagaEventSubscriber;
use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\Saga\CommandRecorder;

#[Saga(name: 'saga.test-two-ids')]
final class TestSagaWithTwoIds
{
    #[SagaId(name: 'orderId')]
    public ?string $orderId = null;

    #[SagaId(name: 'paymentId')]
    public ?string $paymentId = null;

    public int $handled = 0;

    #[SagaEventSubscriber(policy: CreationPolicy::IfMissing)]
    public function onTestSagaTwoIdsEvent(TestSagaTwoIdsEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->orderId = $event->orderId;
        $this->paymentId = $event->paymentId;
        ++$this->handled;
    }
}

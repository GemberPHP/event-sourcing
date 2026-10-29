<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Saga;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Saga\Attribute\SagaEventSubscriber;
use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\Saga\CommandRecorder;

#[Saga(name: 'saga.test-two-subscribers')]
final class TestSagaWithTwoSubscribers
{
    #[SagaId(name: 'twoSubscribersId')]
    public ?string $sagaId = null;

    /**
     * @var list<string>
     */
    public array $isCalled = [];

    #[SagaEventSubscriber(policy: CreationPolicy::IfMissing)]
    public function onFirst(TestSagaTwoSubscribersEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->sagaId = $event->sagaId;
        $this->isCalled[] = __FUNCTION__;
    }

    #[SagaEventSubscriber(policy: CreationPolicy::IfMissing)]
    public function onSecond(TestSagaTwoSubscribersEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->sagaId = $event->sagaId;
        $this->isCalled[] = __FUNCTION__;
    }
}

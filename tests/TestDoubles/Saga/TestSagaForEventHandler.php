<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Saga;

use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Saga\Attribute\SagaEventSubscriber;
use Gember\EventSourcing\Saga\Attribute\SagaId;

#[Saga(name: 'saga.test-event-handler')]
final class TestSagaForEventHandler
{
    #[SagaId(name: 'testSagaId')]
    public ?string $sagaId = null;

    /**
     * @var list<string>
     */
    public array $isCalled = [];

    public function __construct(
        ?string $sagaId = null,
    ) {
        $this->sagaId = $sagaId;
    }

    #[SagaEventSubscriber(policy: CreationPolicy::IfMissing)]
    public function onTestSagaEvent(TestSagaEvent $event, CommandBus $commandBus): void
    {
        $this->isCalled[] = __METHOD__;
        $this->sagaId = $event->sagaId;
    }

    #[SagaEventSubscriber(policy: CreationPolicy::Never)]
    public function onTestSagaSecondEvent(TestSagaSecondEvent $event, CommandBus $commandBus): void
    {
        $this->isCalled[] = __METHOD__;
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Saga;

use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Saga\Attribute\SagaEventSubscriber;
use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;

#[Saga(name: 'saga.test')]
final class TestSaga
{
    #[SagaId(name: 'anotherName')]
    public ?string $someId;

    #[SagaId]
    public ?string $anotherId;

    public function __construct(
        ?string $someId = null,
        ?string $anotherId = null,
    ) {
        $this->someId = $someId;
        $this->anotherId = $anotherId;
    }

    #[SagaEventSubscriber(policy: CreationPolicy::IfMissing)]
    public function onTestUseCaseCreatedEvent(TestUseCaseCreatedEvent $event, CommandBus $commandBus): void {}

    #[SagaEventSubscriber]
    public function onTestUseCaseModifiedEvent(TestUseCaseModifiedEvent $event, CommandBus $commandBus): void {}
}

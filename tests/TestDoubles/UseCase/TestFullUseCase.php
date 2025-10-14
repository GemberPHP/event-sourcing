<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\UseCase\Attribute\DomainCommandHandler;
use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;

final class TestFullUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    public string $domainTag;

    #[DomainTag]
    public string $secondaryId;

    #[DomainCommandHandler]
    public function __invoke(TestUseCaseWithCommand $command): void {}

    #[DomainCommandHandler(policy: CreationPolicy::IfMissing)]
    public function second(TestSecondUseCaseWithCommand $command): void {}

    #[DomainEventSubscriber]
    private function onTestUseCaseCreatedEvent(TestUseCaseCreatedEvent $event): void {}

    #[DomainEventSubscriber]
    private function onTestUseCaseModifiedEvent(TestUseCaseModifiedEvent $event): void {}
}

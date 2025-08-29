<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\UseCase\Attribute\CreationPolicy;
use Gember\EventSourcing\UseCase\Attribute\DomainCommandHandler;
use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;

final class TestUseCaseWithCommandHandler implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private string $domainTag;

    #[DomainCommandHandler]
    public function __invoke(TestUseCaseWithCommand $command): void
    {
        if (!isset($this->domainTag)) {
            return;
        }

        $this->apply(new UseCaseWithCommandTestedEvent($command->domainTag));
    }

    #[DomainCommandHandler(policy: CreationPolicy::IfMissing)]
    public function second(TestSecondUseCaseWithCommand $command): void {}

    #[DomainCommandHandler]
    public function invalidWithParameter(): void {}

    #[DomainCommandHandler]
    public function invalidWithParameterType($command): void {}

    #[DomainEventSubscriber]
    private function onEvent(UseCaseWithCommandTestedEvent $event): void
    {
        $this->domainTag = $event->domainTag;
    }
}

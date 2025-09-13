<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\UseCase\Attribute\DomainCommandHandler;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;

final class TestUseCaseWithCommandHandler implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    public ?string $domainTag = null;

    /**
     * @var list<string>
     */
    public array $isCalled = [];

    #[DomainCommandHandler]
    public function __invoke(TestUseCaseWithCommand $command): void
    {
        $this->domainTag = $command->domainTag;
        $this->isCalled[] = __METHOD__;

        $this->apply(new UseCaseWithCommandTestedEvent($command->domainTag));
    }

    #[DomainCommandHandler(policy: CreationPolicy::IfMissing)]
    public function second(TestSecondUseCaseWithCommand $command): void
    {
        $this->domainTag = $command->domainTag;
        $this->isCalled[] = __METHOD__;
    }

    #[DomainCommandHandler]
    public function invalidWithParameter(): void {}

    #[DomainCommandHandler]
    public function invalidWithParameterType($command): void {}
}

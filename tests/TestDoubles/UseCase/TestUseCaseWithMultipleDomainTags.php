<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\UseCase\Attribute\DomainCommandHandler;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;

final class TestUseCaseWithMultipleDomainTags implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    public string $firstTag;

    #[DomainTag]
    public string $secondTag;

    #[DomainCommandHandler(policy: CreationPolicy::IfMissing)]
    public function __invoke(TestMultipleDomainTagsCommand $command): void
    {
        $this->firstTag = $command->firstTag;
        $this->secondTag = $command->secondTag;
    }
}

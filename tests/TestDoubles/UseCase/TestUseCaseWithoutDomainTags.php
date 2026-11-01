<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;

/**
 * A use case with #[DomainTag] properties can't resolve without them,
 * so this one implements getDomainTags() itself to return none.
 */
final class TestUseCaseWithoutDomainTags implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    public function getDomainTags(): array
    {
        return [];
    }
}

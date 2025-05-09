<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Registry;

use Gember\EventSourcing\Registry\EventRegistry;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Override;

final readonly class TestEventRegistry implements EventRegistry
{
    #[Override]
    public function retrieve(string $eventName): string
    {
        return TestUseCaseCreatedEvent::class;
    }
}

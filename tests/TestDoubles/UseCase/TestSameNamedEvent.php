<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;

/**
 * Has the same event name as UseCaseWithCommandTestedEvent.
 */
#[DomainEvent(name: 'test.use-case-with-command.tested')]
final readonly class TestSameNamedEvent
{
    public function __construct(
        #[DomainTag]
        public string $domainTag,
    ) {}
}

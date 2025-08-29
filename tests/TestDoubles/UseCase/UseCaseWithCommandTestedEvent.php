<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;

#[DomainEvent(name: 'test.use-case-with-command.tesed')]
final readonly class UseCaseWithCommandTestedEvent
{
    public function __construct(
        #[DomainTag]
        public string $domainTag,
    ) {}
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\UseCase\Attribute\DomainTag;

final readonly class TestUseCaseWithCommand
{
    public function __construct(
        #[DomainTag]
        public string $domainTag,
    ) {}
}

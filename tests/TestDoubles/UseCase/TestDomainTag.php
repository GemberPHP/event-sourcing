<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Stringable;

final readonly class TestDomainTag implements Stringable
{
    public function __construct(
        public string $id,
    ) {}

    public function __toString(): string
    {
        return $this->id;
    }
}

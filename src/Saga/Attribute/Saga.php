<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Saga
{
    public function __construct(
        public string $name,
    ) {}
}

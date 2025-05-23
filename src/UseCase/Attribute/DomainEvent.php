<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DomainEvent
{
    public function __construct(
        public string $name,
    ) {}
}

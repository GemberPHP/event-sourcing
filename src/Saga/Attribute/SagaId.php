<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class SagaId
{
    public function __construct(
        public ?string $name = null,
    ) {}
}

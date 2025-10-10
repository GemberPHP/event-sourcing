<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga\Attribute;

use Gember\EventSourcing\Common\CreationPolicy;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class SagaEventSubscriber
{
    public function __construct(
        public CreationPolicy $policy = CreationPolicy::Never,
    ) {}
}

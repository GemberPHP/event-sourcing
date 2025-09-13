<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase\Attribute;

use Attribute;
use Gember\EventSourcing\Common\CreationPolicy;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class DomainCommandHandler
{
    public function __construct(
        public CreationPolicy $policy = CreationPolicy::Never,
    ) {}
}

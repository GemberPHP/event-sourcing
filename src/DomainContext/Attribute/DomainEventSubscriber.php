<?php

declare(strict_types=1);

namespace Gember\EventSourcing\DomainContext\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class DomainEventSubscriber {}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class DomainTag {}

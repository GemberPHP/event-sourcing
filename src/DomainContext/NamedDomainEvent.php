<?php

declare(strict_types=1);

namespace Gember\EventSourcing\DomainContext;

interface NamedDomainEvent
{
    public static function getName(): string;
}

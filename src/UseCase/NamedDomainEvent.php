<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase;

interface NamedDomainEvent
{
    public static function getName(): string;
}

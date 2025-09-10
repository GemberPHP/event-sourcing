<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent;

interface DomainEventResolver
{
    /**
     * @param class-string $eventClassName
     */
    public function resolve(string $eventClassName): DomainEventDefinition;
}

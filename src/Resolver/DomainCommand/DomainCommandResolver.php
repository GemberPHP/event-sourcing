<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainCommand;

interface DomainCommandResolver
{
    /**
     * @param class-string $commandClassName
     */
    public function resolve(string $commandClassName): DomainCommandDefinition;
}

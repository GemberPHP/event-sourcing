<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry;

/**
 * Retrieve domain event (FQCN) based on normalized event name.
 */
interface EventRegistry
{
    /**
     * @throws EventNotRegisteredException
     *
     * @return class-string
     */
    public function retrieve(string $eventName): string;
}

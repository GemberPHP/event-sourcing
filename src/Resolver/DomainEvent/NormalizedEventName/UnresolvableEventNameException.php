<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName;

use Exception;

class UnresolvableEventNameException extends Exception
{
    /**
     * @param class-string $eventClassName
     */
    public static function create(string $eventClassName, string $message): self
    {
        return new self(sprintf('Unresolvable event name for class %s: %s', $eventClassName, $message));
    }
}

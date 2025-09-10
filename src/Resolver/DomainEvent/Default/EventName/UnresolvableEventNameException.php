<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\Default\EventName;

use Exception;

final class UnresolvableEventNameException extends Exception
{
    /**
     * @param class-string $eventClassName
     */
    public static function create(string $eventClassName, string $message): self
    {
        return new self(sprintf('Unresolvable event name for class %s: %s', $eventClassName, $message));
    }
}

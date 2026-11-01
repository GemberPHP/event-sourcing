<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\Event;

use Exception;

final class EventAlreadyRegisteredException extends Exception
{
    /**
     * @param class-string $registeredEventClassName
     * @param class-string $eventClassName
     */
    public static function withEventName(string $eventName, string $registeredEventClassName, string $eventClassName): self
    {
        return new self(sprintf(
            'Event `%s` is already registered for %s, and can\'t be registered for %s too',
            $eventName,
            $registeredEventClassName,
            $eventClassName,
        ));
    }
}

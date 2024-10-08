<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry;

use Exception;

final class EventNotRegisteredException extends Exception
{
    public static function withEventName(string $eventName): self
    {
        return new self(sprintf('Event `%s` not registered', $eventName));
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore;

use Exception;

final class NoEventsForDomainTagsException extends Exception
{
    public static function create(): self
    {
        return new self('No events for the given domain tags are found in event store');
    }
}

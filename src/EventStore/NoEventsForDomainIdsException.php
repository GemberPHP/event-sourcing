<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore;

use Exception;

final class NoEventsForDomainIdsException extends Exception
{
    public static function create(): self
    {
        return new self('No events for the given domain ids are found in event store');
    }
}

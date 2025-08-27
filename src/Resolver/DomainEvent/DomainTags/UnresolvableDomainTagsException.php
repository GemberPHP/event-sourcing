<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\DomainTags;

use Exception;

class UnresolvableDomainTagsException extends Exception
{
    /**
     * @param class-string $eventClassName
     */
    public static function create(string $eventClassName, string $message): self
    {
        return new self(sprintf('Unresolvable domainTags for event %s: %s', $eventClassName, $message));
    }
}

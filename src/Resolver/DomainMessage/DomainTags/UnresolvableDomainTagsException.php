<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainMessage\DomainTags;

use Exception;

class UnresolvableDomainTagsException extends Exception
{
    /**
     * @param class-string $messageClassName
     */
    public static function create(string $messageClassName, string $message): self
    {
        return new self(sprintf(
            'Unresolvable domainTags for domain message (event/command) %s: %s',
            $messageClassName,
            $message,
        ));
    }
}

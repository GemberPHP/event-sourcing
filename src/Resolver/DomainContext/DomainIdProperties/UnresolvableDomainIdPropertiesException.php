<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties;

use Exception;

class UnresolvableDomainIdPropertiesException extends Exception
{
    /**
     * @param class-string $domainContextClassName
     */
    public static function create(string $domainContextClassName, string $message): self
    {
        return new self(sprintf('Unresolvable domainId properties for context %s: %s', $domainContextClassName, $message));
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\DomainIdProperties;

use Exception;

class UnresolvableDomainIdPropertiesException extends Exception
{
    /**
     * @param class-string $useCaseClassName
     */
    public static function create(string $useCaseClassName, string $message): self
    {
        return new self(sprintf('Unresolvable domainId properties for use case %s: %s', $useCaseClassName, $message));
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\DomainTagProperties;

use Exception;

class UnresolvableDomainTagPropertiesException extends Exception
{
    /**
     * @param class-string $useCaseClassName
     */
    public static function create(string $useCaseClassName, string $message): self
    {
        return new self(sprintf('Unresolvable domainTag properties for use case %s: %s', $useCaseClassName, $message));
    }
}

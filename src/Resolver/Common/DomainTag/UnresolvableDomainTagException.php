<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Common\DomainTag;

use Exception;

final class UnresolvableDomainTagException extends Exception
{
    /**
     * @param class-string $className
     */
    public static function create(string $className, string $message): self
    {
        return new self(sprintf(
            'Unresolvable domain tags for class %s: %s',
            $className,
            $message,
        ));
    }
}

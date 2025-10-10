<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga;

use Exception;

final class UnresolvableSagaException extends Exception
{
    /**
     * @param class-string $sagaClassName
     */
    public static function missingSagaId(string $sagaClassName): self
    {
        return new self(sprintf('No saga ids found for saga %s', $sagaClassName));
    }

    /**
     * @param class-string $sagaClassName
     */
    public static function tooManySagaIds(string $sagaClassName): self
    {
        return new self(sprintf('Multiple saga ids found for saga %s', $sagaClassName));
    }
}

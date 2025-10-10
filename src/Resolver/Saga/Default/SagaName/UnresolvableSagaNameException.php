<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga\Default\SagaName;

use Exception;

final class UnresolvableSagaNameException extends Exception
{
    /**
     * @param class-string $sagaClassName
     */
    public static function create(string $sagaClassName, string $message): self
    {
        return new self(sprintf('Unresolvable saga name for class %s: %s', $sagaClassName, $message));
    }
}

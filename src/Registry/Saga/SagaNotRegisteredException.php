<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\Saga;

use Exception;

final class SagaNotRegisteredException extends Exception
{
    public static function withSagaIdName(string $sagaIdName): self
    {
        return new self(sprintf('Saga `%s` not registered', $sagaIdName));
    }
}

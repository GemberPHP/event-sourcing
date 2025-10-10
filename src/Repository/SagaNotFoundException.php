<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Exception;

final class SagaNotFoundException extends Exception
{
    public static function create(): self
    {
        return new self('Saga not found');
    }
}

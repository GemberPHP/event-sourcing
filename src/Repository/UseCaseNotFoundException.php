<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Exception;

final class UseCaseNotFoundException extends Exception
{
    public static function create(): self
    {
        return new self('Use case not found');
    }
}

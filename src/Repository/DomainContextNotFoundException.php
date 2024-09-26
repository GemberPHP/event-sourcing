<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Exception;

final class DomainContextNotFoundException extends Exception
{
    public static function create(): self
    {
        return new self('Domain context not found');
    }
}

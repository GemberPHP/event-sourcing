<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\File\Reflector;

use Exception;

final class ReflectionFailedException extends Exception
{
    public static function classNotFound(string $file): self
    {
        return new self(sprintf('Class not found in file %s', $file));
    }
}

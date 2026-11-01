<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\File\Reflector;

use Gember\EventSourcing\Util\File\Reflector\Reflector;
use Gember\EventSourcing\Util\File\Reflector\ReflectionFailedException;
use ReflectionClass;

final class TestReflector implements Reflector
{
    /**
     * @var array<string, class-string>
     */
    public array $files = [];

    public function reflectClassFromFile(string $file): ReflectionClass
    {
        if (!array_key_exists($file, $this->files)) {
            throw ReflectionFailedException::classNotFound($file);
        }

        return new ReflectionClass($this->files[$file]);
    }
}

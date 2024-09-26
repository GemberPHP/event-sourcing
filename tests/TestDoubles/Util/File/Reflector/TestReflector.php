<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\File\Reflector;

use Gember\EventSourcing\Util\File\Reflector\Reflector;
use ReflectionClass;

final class TestReflector implements Reflector
{
    /**
     * @var array<string, class-string>
     */
    public array $files = [];

    public function reflectClassFromFile(string $file): ReflectionClass
    {
        return new ReflectionClass($this->files[$file]);
    }
}

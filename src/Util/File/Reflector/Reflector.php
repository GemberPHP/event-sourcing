<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\File\Reflector;

use ReflectionClass;

interface Reflector
{
    /**
     * @param non-empty-string $file
     *
     * @throws ReflectionFailedException
     *
     * @return ReflectionClass<object>
     */
    public function reflectClassFromFile(string $file): ReflectionClass;
}

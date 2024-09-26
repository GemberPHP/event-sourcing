<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\String\Inflector;

interface Inflector
{
    public function toSnakeCase(string $value): string;
}

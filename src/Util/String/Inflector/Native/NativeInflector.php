<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\String\Inflector\Native;

use Gember\EventSourcing\Util\String\Inflector\Inflector;
use Override;

final readonly class NativeInflector implements Inflector
{
    #[Override]
    public function toSnakeCase(string $value): string
    {
        return mb_strtolower((string) preg_replace('~(?<=\w)([A-Z])~u', '_$1', $value));
    }
}

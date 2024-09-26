<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\String\FriendlyClassNamer\Native;

use Gember\EventSourcing\Util\String\FriendlyClassNamer\FriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Inflector;
use Override;

final readonly class NativeFriendlyClassNamer implements FriendlyClassNamer
{
    public function __construct(
        private Inflector $inflector,
    ) {}

    #[Override]
    public function createFriendlyClassName(string $className): string
    {
        return str_replace(['\\', '_'], ['.', '-'], $this->inflector->toSnakeCase($className));
    }
}

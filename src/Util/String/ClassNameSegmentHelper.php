<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\String;

final readonly class ClassNameSegmentHelper
{
    /**
     * @param class-string $className
     */
    public static function getLastSegment(string $className): string
    {
        if (!str_contains($className, '\\')) {
            return $className;
        }

        return substr($className, strrpos($className, '\\') + 1);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\String\FriendlyClassNamer;

interface FriendlyClassNamer
{
    /**
     * @param class-string $className
     */
    public function createFriendlyClassName(string $className): string;
}

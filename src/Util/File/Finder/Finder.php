<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\File\Finder;

interface Finder
{
    /**
     * @return list<string>
     */
    public function getFiles(string $path): array;
}

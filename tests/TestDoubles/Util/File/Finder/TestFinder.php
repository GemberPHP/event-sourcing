<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\File\Finder;

use Gember\EventSourcing\Util\File\Finder\Finder;

final class TestFinder implements Finder
{
    /**
     * @var list<string>
     */
    public array $files = [];

    public function getFiles(string $path): array
    {
        return $this->files;
    }
}

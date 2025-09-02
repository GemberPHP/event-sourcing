<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\File\Finder\Native;

use Gember\EventSourcing\Util\File\Finder\Finder;
use Override;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

final readonly class NativeFinder implements Finder
{
    private const string REGEX_PHP_FILE = '/^.+\.php$/i';

    #[Override]
    public function getFiles(string $path): array
    {
        $filesIterator = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)),
            self::REGEX_PHP_FILE,
            RegexIterator::GET_MATCH,
        );

        $files = [];

        /** @var array<int, string> $filePath */
        foreach ($filesIterator as $filePath) {
            if (isset($filePath[0]) === false) {
                continue;
            }

            $files[] = $filePath[0];
        }

        return $files;
    }
}

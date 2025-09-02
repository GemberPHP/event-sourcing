<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\File\Finder\Native;

use Gember\EventSourcing\Util\File\Finder\Native\NativeFinder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class NativeFinderTest extends TestCase
{
    private NativeFinder $finder;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = new NativeFinder();
    }

    #[Test]
    public function itShouldRetrieveFilesFromFolder(): void
    {
        $files = iterator_to_array($this->finder->getFiles(__DIR__ . '/../../'));

        sort($files);

        $files = array_map(
            fn($file) => str_replace(__DIR__ . '/../../', '', $file),
            $files,
        );

        self::assertSame([
            'Finder/Native/NativeFinderTest.php',
            'Reflector/Native/NativeReflectorTest.php',
            'Reflector/ReflectionFailedExceptionTest.php',
        ], $files);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\File\Reflector;

use Gember\EventSourcing\Util\File\Reflector\ReflectionFailedException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ReflectionFailedExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateClassNotFoundException(): void
    {
        $exception = ReflectionFailedException::classNotFound('/path/to/file/Class.php');

        self::assertSame('Class not found in file /path/to/file/Class.php', $exception->getMessage());
    }
}

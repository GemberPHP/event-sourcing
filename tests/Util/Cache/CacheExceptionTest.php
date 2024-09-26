<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Cache;

use Gember\EventSourcing\Util\Cache\CacheException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Exception;

/**
 * @internal
 */
final class CacheExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateWithException(): void
    {
        $exception = CacheException::withException($previous = new Exception('It failed'));

        self::assertSame('Cache failed: It failed', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }
}

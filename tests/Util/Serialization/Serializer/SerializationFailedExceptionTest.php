<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Serialization\Serializer;

use Gember\EventSourcing\Util\Serialization\Serializer\SerializationFailedException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Exception;

/**
 * @internal
 */
final class SerializationFailedExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateWithMessage(): void
    {
        $exception = SerializationFailedException::withMessage('It failed');

        self::assertSame('Serialization failed: It failed', $exception->getMessage());
    }

    #[Test]
    public function itShouldCreateWithException(): void
    {
        $exception = SerializationFailedException::withException($previous = new Exception('It failed'));

        self::assertSame('Serialization failed: It failed', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }
}

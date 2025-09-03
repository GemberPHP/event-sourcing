<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Serialization\Serializer;

use Gember\DependencyContracts\Util\Serialization\Serializer\SerializationFailedException;
use Gember\EventSourcing\Util\Serialization\Serializer\SerializationFailedCollectionException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SerializationFailedCollectionExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateWithExceptions(): void
    {
        $exception = SerializationFailedCollectionException::withExceptions(
            'All failed',
            $innerException1 = SerializationFailedException::withMessage('Inner 1'),
            $innerException2 = SerializationFailedException::withMessage('Inner 2'),
            $innerException3 = SerializationFailedException::withMessage('Inner 3'),
        );

        self::assertSame('Serialization failed: All failed', $exception->getMessage());
        self::assertSame([
            $innerException1,
            $innerException2,
            $innerException3,
        ], $exception->getExceptions());
    }
}

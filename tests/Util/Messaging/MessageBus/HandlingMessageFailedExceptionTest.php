<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Messaging\MessageBus;

use Gember\EventSourcing\Util\Messaging\MessageBus\HandlingMessageFailedException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Exception;

/**
 * @internal
 */
final class HandlingMessageFailedExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateWithException(): void
    {
        $exception = HandlingMessageFailedException::withException($previous = new Exception('It failed'));

        self::assertSame('Handling message failed: It failed', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }
}

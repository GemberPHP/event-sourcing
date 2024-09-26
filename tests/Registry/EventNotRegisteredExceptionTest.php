<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Registry;

use Gember\EventSourcing\Registry\EventNotRegisteredException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EventNotRegisteredExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateException(): void
    {
        $exception = EventNotRegisteredException::withEventName('event-name');

        self::assertSame('Event `event-name` not registered', $exception->getMessage());
    }
}

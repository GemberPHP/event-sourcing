<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Registry\Event;

use Gember\EventSourcing\Registry\Event\EventNotRegisteredException;
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

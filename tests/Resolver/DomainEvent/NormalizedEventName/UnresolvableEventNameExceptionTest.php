<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\NormalizedEventName;

use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\UnresolvableEventNameException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UnresolvableEventNameExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateException(): void
    {
        $exception = UnresolvableEventNameException::create(TestUseCaseCreatedEvent::class, 'It failed');

        self::assertSame(
            'Unresolvable event name for class Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent: It failed',
            $exception->getMessage(),
        );
    }
}

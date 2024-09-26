<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\NormalizedEventName;

use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\UnresolvableEventNameCollectionException;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\UnresolvableEventNameException;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UnresolvableEventNameCollectionExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateCollectionException(): void
    {
        $exception = UnresolvableEventNameCollectionException::withExceptions(
            TestDomainContextCreatedEvent::class,
            'It failed',
            $exception1 = UnresolvableEventNameException::create(TestDomainContextCreatedEvent::class, 'It failed'),
            $exception2 = UnresolvableEventNameException::create(TestDomainContextCreatedEvent::class, 'Also failed'),
        );

        self::assertSame(
            'Unresolvable event name for class Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent: It failed',
            $exception->getMessage(),
        );
        self::assertSame([$exception1, $exception2], $exception->getExceptions());
    }
}

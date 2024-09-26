<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\DomainIds;

use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\UnresolvableDomainIdsException;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UnresolvableDomainIdsExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateException(): void
    {
        $exception = UnresolvableDomainIdsException::create(TestDomainContextCreatedEvent::class, 'It failed');

        self::assertSame(
            'Unresolvable domainIds for event Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent: It failed',
            $exception->getMessage(),
        );
    }
}

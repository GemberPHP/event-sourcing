<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\DomainIds;

use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\UnresolvableDomainIdsCollectionException;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\UnresolvableDomainIdsException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UnresolvableDomainIdsCollectionExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateCollectionException(): void
    {
        $exception = UnresolvableDomainIdsCollectionException::withExceptions(
            TestUseCaseCreatedEvent::class,
            'It failed',
            $exception1 = UnresolvableDomainIdsException::create(TestUseCaseCreatedEvent::class, 'It failed'),
            $exception2 = UnresolvableDomainIdsException::create(TestUseCaseCreatedEvent::class, 'Also failed'),
        );

        self::assertSame(
            'Unresolvable domainIds for event Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent: It failed',
            $exception->getMessage(),
        );
        self::assertSame([$exception1, $exception2], $exception->getExceptions());
    }
}

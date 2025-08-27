<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\DomainTags;

use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\UnresolvableDomainTagsCollectionException;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\UnresolvableDomainTagsException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UnresolvableDomainTagsCollectionExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateCollectionException(): void
    {
        $exception = UnresolvableDomainTagsCollectionException::withExceptions(
            TestUseCaseCreatedEvent::class,
            'It failed',
            $exception1 = UnresolvableDomainTagsException::create(TestUseCaseCreatedEvent::class, 'It failed'),
            $exception2 = UnresolvableDomainTagsException::create(TestUseCaseCreatedEvent::class, 'Also failed'),
        );

        self::assertSame(
            'Unresolvable domainTags for event Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent: It failed',
            $exception->getMessage(),
        );
        self::assertSame([$exception1, $exception2], $exception->getExceptions());
    }
}

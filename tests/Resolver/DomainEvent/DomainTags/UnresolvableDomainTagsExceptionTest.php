<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\DomainTags;

use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\UnresolvableDomainTagsException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UnresolvableDomainTagsExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateException(): void
    {
        $exception = UnresolvableDomainTagsException::create(TestUseCaseCreatedEvent::class, 'It failed');

        self::assertSame(
            'Unresolvable domainTags for event Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent: It failed',
            $exception->getMessage(),
        );
    }
}

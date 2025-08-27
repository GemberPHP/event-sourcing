<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\DomainTags\Interface;

use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\Interface\InterfaceDomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\UnresolvableDomainTagsException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestDomainTag;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class InterfaceDomainTagsResolverTest extends TestCase
{
    private InterfaceDomainTagsResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new InterfaceDomainTagsResolver();
    }

    #[Test]
    public function itShouldThrowExceptionWhenDomainTagsCannotBeResolved(): void
    {
        self::expectException(UnresolvableDomainTagsException::class);

        $this->resolver->resolve(new TestUseCaseCreatedEvent(
            '7dc468da-5285-4ba0-bba6-fbdd4068d032',
            '8fb156a6-58a6-41de-92dc-f4dc52294581',
        ));
    }

    #[Test]
    public function itShouldResolveDomainTags(): void
    {
        $domainTags = $this->resolver->resolve(new TestUseCaseModifiedEvent());

        self::assertEquals([
            '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a',
            new TestDomainTag('afb200a7-4f94-4d40-87b2-50575a1553c7'),
        ], $domainTags);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\DomainTags\Stacked;

use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\Attribute\AttributeDomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\Interface\InterfaceDomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\Stacked\StackedDomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\UnresolvableDomainTagsCollectionException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestDomainTag;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Override;

/**
 * @internal
 */
final class StackedDomainTagsResolverTest extends TestCase
{
    private StackedDomainTagsResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new StackedDomainTagsResolver([
            new AttributeDomainTagsResolver(
                new ReflectorAttributeResolver(),
            ),
            new InterfaceDomainTagsResolver(),
        ]);
    }

    #[Test]
    public function itShouldThrowExceptionIfAllResolversCannotResolveName(): void
    {
        self::expectException(UnresolvableDomainTagsCollectionException::class);
        self::expectExceptionMessage('None DomainTagsResolver could resolve domainTag');

        $this->resolver->resolve(new stdClass());
    }

    #[Test]
    public function itShouldResolveName(): void
    {
        $eventName = $this->resolver->resolve(new TestUseCaseModifiedEvent());

        self::assertEquals([
            '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a',
            new TestDomainTag('afb200a7-4f94-4d40-87b2-50575a1553c7'),
        ], $eventName);

        $eventName = $this->resolver->resolve(new TestUseCaseCreatedEvent(
            '38d07c3f-7442-4ac0-9471-551a4d5ffcd5',
            'bc018d4f-0bd9-4d30-a25e-d345bfa5bc35',
        ));

        self::assertSame([
            '38d07c3f-7442-4ac0-9471-551a4d5ffcd5',
            'bc018d4f-0bd9-4d30-a25e-d345bfa5bc35',
        ], $eventName);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\DomainIds\Stacked;

use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\Attribute\AttributeDomainIdsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\Interface\InterfaceDomainIdsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\Stacked\StackedDomainIdsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\UnresolvableDomainIdsCollectionException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestDomainId;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Override;

/**
 * @internal
 */
final class StackedDomainIdsResolverTest extends TestCase
{
    private StackedDomainIdsResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new StackedDomainIdsResolver([
            new AttributeDomainIdsResolver(
                new ReflectorAttributeResolver(),
            ),
            new InterfaceDomainIdsResolver(),
        ]);
    }

    #[Test]
    public function itShouldThrowExceptionIfAllResolversCannotResolveName(): void
    {
        self::expectException(UnresolvableDomainIdsCollectionException::class);
        self::expectExceptionMessage('None DomainIdsResolver could resolve domainId');

        $this->resolver->resolve(new stdClass());
    }

    #[Test]
    public function itShouldResolveName(): void
    {
        $eventName = $this->resolver->resolve(new TestUseCaseModifiedEvent());

        self::assertEquals([
            '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a',
            new TestDomainId('afb200a7-4f94-4d40-87b2-50575a1553c7'),
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

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\NormalizedEventName\Stacked;

use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Attribute\AttributeNormalizedEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Interface\InterfaceNormalizedEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Stacked\StackedNormalizedEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\UnresolvableEventNameCollectionException;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextModifiedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use stdClass;

/**
 * @internal
 */
final class StackedNormalizedEventNameResolverTest extends TestCase
{
    private StackedNormalizedEventNameResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new StackedNormalizedEventNameResolver([
            new AttributeNormalizedEventNameResolver(
                new ReflectorAttributeResolver(),
            ),
            new InterfaceNormalizedEventNameResolver(),
        ]);
    }

    #[Test]
    public function itShouldThrowExceptionIfAllResolversCannotResolveName(): void
    {
        self::expectException(UnresolvableEventNameCollectionException::class);
        self::expectExceptionMessage('None NormalizedEventNameResolver could resolve event name');

        $this->resolver->resolve(stdClass::class);
    }

    #[Test]
    public function itShouldResolveName(): void
    {
        $eventName = $this->resolver->resolve(TestDomainContextModifiedEvent::class);

        self::assertSame('test.domain-context.modified', $eventName);

        $eventName = $this->resolver->resolve(TestDomainContextCreatedEvent::class);

        self::assertSame('test.domain-context.created', $eventName);
    }
}
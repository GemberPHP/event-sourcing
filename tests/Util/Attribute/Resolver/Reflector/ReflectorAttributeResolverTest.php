<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Attribute\Resolver\Reflector;

use Gember\EventSourcing\DomainContext\Attribute\DomainEvent;
use Gember\EventSourcing\DomainContext\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\DomainContext\Attribute\DomainId;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContext;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Method;
use Gember\EventSourcing\Util\Attribute\Resolver\Parameter;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class ReflectorAttributeResolverTest extends TestCase
{
    private ReflectorAttributeResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new ReflectorAttributeResolver();
    }

    #[Test]
    public function itShouldGetPropertiesNamesWithAttribute(): void
    {
        $names = $this->resolver->getPropertyNamesWithAttribute(
            TestDomainContext::class,
            DomainId::class,
        );

        self::assertSame([
            'domainId',
            'secondaryId',
        ], $names);
    }

    #[Test]
    public function itShouldGetMethodsWithAttribute(): void
    {
        $methods = $this->resolver->getMethodsWithAttribute(
            TestDomainContext::class,
            DomainEventSubscriber::class,
        );

        self::assertEquals(
            [
                new Method(
                    'onTestDomainContextCreatedEvent',
                    [
                        new Parameter('event', TestDomainContextCreatedEvent::class),
                    ],
                ),
            ],
            $methods,
        );
    }

    #[Test]
    public function itShouldGetAttributesForClass(): void
    {
        $attributes = $this->resolver->getAttributesForClass(
            TestDomainContextCreatedEvent::class,
            DomainEvent::class,
        );

        self::assertEquals([
            new DomainEvent('test.domain-context.created'),
        ], $attributes);
    }
}

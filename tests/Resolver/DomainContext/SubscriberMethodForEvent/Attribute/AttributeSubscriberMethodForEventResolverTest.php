<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainContext\SubscriberMethodForEvent\Attribute;

use Gember\EventSourcing\Resolver\DomainContext\SubscriberMethodForEvent\Attribute\AttributeSubscriberMethodForEventResolver;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextWithSubscribers;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use stdClass;

/**
 * @internal
 */
final class AttributeSubscriberMethodForEventResolverTest extends TestCase
{
    private AttributeSubscriberMethodForEventResolver $resolver; // @phpstan-ignore-line

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeSubscriberMethodForEventResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldReturnNullWhenMethodNotFound(): void
    {
        $method = $this->resolver->resolve(
            TestDomainContextWithSubscribers::class, // @phpstan-ignore-line
            stdClass::class,
        );

        self::assertNull($method);
    }

    #[Test]
    public function itShouldResolveSubscriberMethodForGivenEvent(): void
    {
        $method = $this->resolver->resolve(
            TestDomainContextWithSubscribers::class, // @phpstan-ignore-line
            TestDomainContextCreatedEvent::class,
        );

        self::assertSame('onTestDomainContextCreatedEvent', $method);
    }
}

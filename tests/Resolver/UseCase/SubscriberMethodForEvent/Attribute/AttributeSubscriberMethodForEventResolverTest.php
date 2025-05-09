<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase\SubscriberMethodForEvent\Attribute;

use Gember\EventSourcing\Resolver\UseCase\SubscriberMethodForEvent\Attribute\AttributeSubscriberMethodForEventResolver;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithSubscribers;
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
    private AttributeSubscriberMethodForEventResolver $resolver;

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
            TestUseCaseWithSubscribers::class, // @phpstan-ignore-line
            stdClass::class,
        );

        self::assertNull($method);
    }

    #[Test]
    public function itShouldResolveSubscriberMethodForGivenEvent(): void
    {
        $method = $this->resolver->resolve(
            TestUseCaseWithSubscribers::class, // @phpstan-ignore-line
            TestUseCaseCreatedEvent::class,
        );

        self::assertSame('onTestUseCaseCreatedEvent', $method);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\Default\EventName\Attribute;

use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\UnresolvableEventNameException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Attribute\AttributeEventNameResolver;
use Override;
use stdClass;

/**
 * @internal
 */
final class AttributeEventNameResolverTest extends TestCase
{
    private AttributeEventNameResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeEventNameResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldResolveEventNameFromAttribute(): void
    {
        $eventName = $this->resolver->resolve(TestUseCaseCreatedEvent::class);

        self::assertSame('test.use-case.created', $eventName);
    }

    #[Test]
    public function itShouldFailResolveWhenAttributeIsNotSet(): void
    {
        self::expectException(UnresolvableEventNameException::class);

        $this->resolver->resolve(stdClass::class);
    }
}

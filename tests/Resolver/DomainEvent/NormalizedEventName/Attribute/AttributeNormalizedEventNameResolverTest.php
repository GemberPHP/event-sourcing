<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\NormalizedEventName\Attribute;

use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Attribute\AttributeNormalizedEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\UnresolvableEventNameException;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use stdClass;

/**
 * @internal
 */
final class AttributeNormalizedEventNameResolverTest extends TestCase
{
    private AttributeNormalizedEventNameResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeNormalizedEventNameResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldThrowExceptionWhenEventNameCannotBeResolved(): void
    {
        self::expectException(UnresolvableEventNameException::class);

        $this->resolver->resolve(stdClass::class);
    }

    #[Test]
    public function itShouldResolveEventName(): void
    {
        $eventName = $this->resolver->resolve(TestDomainContextCreatedEvent::class);

        self::assertSame('test.domain-context.created', $eventName);
    }
}

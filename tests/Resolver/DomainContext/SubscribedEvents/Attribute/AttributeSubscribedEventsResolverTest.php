<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainContext\SubscribedEvents\Attribute;

use Gember\EventSourcing\Resolver\DomainContext\SubscribedEvents\Attribute\AttributeSubscribedEventsResolver;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextWithSubscribers;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class AttributeSubscribedEventsResolverTest extends TestCase
{
    private AttributeSubscribedEventsResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeSubscribedEventsResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldResolveAllDomainEventsUsedInDomainContextt(): void
    {
        $domainEvents = $this->resolver->resolve(TestDomainContextWithSubscribers::class); // @phpstan-ignore-line

        self::assertSame([
            TestDomainContextCreatedEvent::class,
            TestDomainContextModifiedEvent::class,
        ], $domainEvents);
    }
}

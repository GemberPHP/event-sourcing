<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\Cached;

use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\Common\SagaId\Attribute\AttributeSagaIdResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Cached\CachedDomainEventResolverDecorator;
use Gember\EventSourcing\Resolver\DomainEvent\Default\DefaultDomainEventResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Attribute\AttributeEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\Util\Cache\TestCache;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Gember\EventSourcing\Util\Cache\CacheKey;

/**
 * @internal
 */
final class CachedDomainEventResolverDecoratorTest extends TestCase
{
    private TestCache $cache;
    private CachedDomainEventResolverDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->decorator = new CachedDomainEventResolverDecorator(
            new DefaultDomainEventResolver(
                new AttributeEventNameResolver($attributeResolver = new ReflectorAttributeResolver()),
                new AttributeDomainTagResolver($attributeResolver),
                new AttributeSagaIdResolver($attributeResolver),
            ),
            $this->cache = new TestCache(),
        );
    }

    #[Test]
    public function itShouldResolveFromCache(): void
    {
        $expectedDefinition = new DomainEventDefinition(
            TestUseCaseCreatedEvent::class,
            'test.use-case.created',
            [
                new DomainTagDefinition('id', DomainTagType::Property),
                new DomainTagDefinition('secondaryId', DomainTagType::Property),
            ],
            [],
        );

        $this->cache->set(CacheKey::create('resolver.domain_event', stdClass::class), json_encode($expectedDefinition->toPayload()));

        $definition = $this->decorator->resolve(stdClass::class);

        self::assertEquals($expectedDefinition, $definition);
    }

    #[Test]
    public function itShouldStoreInCache(): void
    {
        $definition = $this->decorator->resolve(TestUseCaseCreatedEvent::class);

        $cachedDefinition = $this->cache->get(CacheKey::create('resolver.domain_event', TestUseCaseCreatedEvent::class));

        self::assertEquals(DomainEventDefinition::fromPayload(json_decode($cachedDefinition, true)), $definition);
    }
}

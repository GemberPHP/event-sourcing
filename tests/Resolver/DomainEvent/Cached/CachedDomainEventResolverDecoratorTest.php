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
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

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
            new NativeFriendlyClassNamer(new NativeInflector()),
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

        $this->cache->set('gember.resolver.domain_event.std-class', json_encode($expectedDefinition->toPayload()));

        $definition = $this->decorator->resolve(stdClass::class);

        self::assertEquals($expectedDefinition, $definition);
    }

    #[Test]
    public function itShouldStoreInCache(): void
    {
        $definition = $this->decorator->resolve(TestUseCaseCreatedEvent::class);

        $cachedDefinition = $this->cache->get('gember.resolver.domain_event.gember.event-sourcing.test.test-doubles.use-case.test-use-case-created-event');

        self::assertEquals(DomainEventDefinition::fromPayload(json_decode($cachedDefinition, true)), $definition);
    }
}

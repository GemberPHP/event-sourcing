<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Saga\Cached;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Resolver\Common\SagaId\Attribute\AttributeSagaIdResolver;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdDefinition;
use Gember\EventSourcing\Resolver\Saga\Default\DefaultSagaResolver;
use Gember\EventSourcing\Resolver\Saga\Default\EventSubscriber\Attribute\AttributeSagaEventSubscriberResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Attribute\AttributeSagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\ClassName\ClassNameSagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Interface\InterfaceSagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Stacked\StackedSagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\SagaDefinition;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSaga;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\Util\Cache\TestCache;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\Saga\Cached\CachedSagaResolverDecorator;
use Override;
use stdClass;

/**
 * @internal
 */
final class CachedSagaResolverDecoratorTest extends TestCase
{
    private TestCache $cache;
    private CachedSagaResolverDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->decorator = new CachedSagaResolverDecorator(
            new DefaultSagaResolver(
                new StackedSagaNameResolver(
                    [
                        new AttributeSagaNameResolver($attributeResolver = new ReflectorAttributeResolver()),
                        new InterfaceSagaNameResolver(),
                    ],
                    new ClassNameSagaNameResolver(new NativeFriendlyClassNamer(new NativeInflector())),
                ),
                new AttributeSagaIdResolver($attributeResolver),
                new AttributeSagaEventSubscriberResolver($attributeResolver),
            ),
            $this->cache = new TestCache(),
            new NativeFriendlyClassNamer(new NativeInflector()),
        );
    }

    #[Test]
    public function itShouldResolveFromCache(): void
    {
        $expectedDefinition = new SagaDefinition(
            TestSaga::class,
            'saga.test',
            new SagaIdDefinition('anotherName', 'someId'),
            [
                new SagaEventSubscriberDefinition(
                    TestUseCaseCreatedEvent::class,
                    'onTestUseCaseCreatedEvent',
                    CreationPolicy::IfMissing,
                ),
                new SagaEventSubscriberDefinition(
                    TestUseCaseModifiedEvent::class,
                    'onTestUseCaseModifiedEvent',
                    CreationPolicy::Never,
                ),
            ],
        );

        $this->cache->set('gember.resolver.saga.std-class', json_encode($expectedDefinition->toPayload()));

        $definition = $this->decorator->resolve(stdClass::class);

        self::assertEquals($expectedDefinition, $definition);
    }

    #[Test]
    public function itShouldStoreInCache(): void
    {
        $definition = $this->decorator->resolve(TestSaga::class);

        $cachedDefinition = $this->cache->get('gember.resolver.saga.gember.event-sourcing.test.test-doubles.saga.test-saga');

        self::assertEquals(SagaDefinition::fromPayload(json_decode($cachedDefinition, true)), $definition);
    }
}

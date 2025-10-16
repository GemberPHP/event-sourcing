<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Registry\Saga\Cached;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Registry\Saga\Reflector\ReflectorSagaRegistry;
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
use Gember\EventSourcing\Util\File\Finder\Native\NativeFinder;
use Gember\EventSourcing\Util\File\Reflector\Native\NativeReflector;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Registry\Saga\Cached\CachedSagaRegistryDecorator;
use Override;

/**
 * @internal
 */
final class CachedSagaRegistryDecoratorTest extends TestCase
{
    private TestCache $cache;
    private CachedSagaRegistryDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->decorator = new CachedSagaRegistryDecorator(
            new ReflectorSagaRegistry(
                new NativeFinder(),
                new NativeReflector(),
                new DefaultSagaResolver(
                    new StackedSagaNameResolver(
                        [
                            new AttributeSagaNameResolver($attributeResolver = new ReflectorAttributeResolver()),
                            new InterfaceSagaNameResolver(),
                        ],
                        new ClassNameSagaNameResolver(new NativeFriendlyClassNamer(new NativeInflector())),
                    ),
                    new AttributeSagaIdResolver($attributeResolver),
                    $eventSubscriberResolver = new AttributeSagaEventSubscriberResolver($attributeResolver),
                ),
                $eventSubscriberResolver,
                __DIR__ . '/../../../TestDoubles/Saga',
            ),
            $this->cache = new TestCache(),
        );
    }

    #[Test]
    public function itShouldResolveFromCache(): void
    {
        $expectedDefinition = [
            new SagaDefinition(
                TestSaga::class,
                'saga.test',
                [
                    new SagaIdDefinition('anotherName', 'someId'),
                ],
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
            ),
        ];

        $this->cache->set('gember.registry.saga.some', json_encode([
            [
                'sagaClassName' => TestSaga::class,
                'sagaName' => 'saga.test',
                'sagaIds' => [
                    [
                        'sagaIdName' => 'anotherName',
                        'propertyName' => 'someId',
                    ],
                ],
                'eventSubscribers' => [
                    [
                        'eventClassName' => TestUseCaseCreatedEvent::class,
                        'methodName' => 'onTestUseCaseCreatedEvent',
                        'policy' => 'if_missing',
                    ],
                    [
                        'eventClassName' => TestUseCaseModifiedEvent::class,
                        'methodName' => 'onTestUseCaseModifiedEvent',
                        'policy' => 'never',
                    ],
                ],
            ],
        ]));

        $definition = $this->decorator->retrieve('some');

        self::assertEquals($expectedDefinition, $definition);
    }

    #[Test]
    public function itShouldStoreInCache(): void
    {
        $this->decorator->retrieve('anotherName');

        $cachedDefinition = $this->cache->get('gember.registry.saga.anotherName');

        self::assertSame(json_encode([
            [
                'sagaClassName' => TestSaga::class,
                'sagaName' => 'saga.test',
                'sagaIds' => [
                    [
                        'sagaIdName' => 'anotherName',
                        'propertyName' => 'someId',
                    ],
                    [
                        'sagaIdName' => 'anotherId',
                        'propertyName' => 'anotherId',
                    ],
                ],
                'eventSubscribers' => [
                    [
                        'eventClassName' => TestUseCaseCreatedEvent::class,
                        'methodName' => 'onTestUseCaseCreatedEvent',
                        'policy' => 'if_missing',
                    ],
                    [
                        'eventClassName' => TestUseCaseModifiedEvent::class,
                        'methodName' => 'onTestUseCaseModifiedEvent',
                        'policy' => 'never',
                    ],
                ],
            ],
        ]), $cachedDefinition);
    }
}

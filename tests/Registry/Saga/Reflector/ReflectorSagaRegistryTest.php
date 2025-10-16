<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Registry\Saga\Reflector;

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
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\File\Finder\Native\NativeFinder;
use Gember\EventSourcing\Util\File\Reflector\Native\NativeReflector;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Registry\Saga\Reflector\ReflectorSagaRegistry;
use Override;

/**
 * @internal
 */
final class ReflectorSagaRegistryTest extends TestCase
{
    private ReflectorSagaRegistry $registry;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new ReflectorSagaRegistry(
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
        );
    }

    #[Test]
    public function itShouldReturnEmptyListWhenIdNotRegistered(): void
    {
        self::assertSame([], $this->registry->retrieve('someId'));
    }

    #[Test]
    public function itShouldRetrieveSagasByIdName(): void
    {
        $definitions = $this->registry->retrieve('anotherName');

        self::assertEquals([
            new SagaDefinition(
                TestSaga::class,
                'saga.test',
                [
                    new SagaIdDefinition('anotherName', 'someId'),
                    new SagaIdDefinition('anotherId', 'anotherId'),
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
        ], $definitions);
    }
}

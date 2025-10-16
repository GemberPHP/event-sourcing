<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Saga\Default;

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
use Gember\EventSourcing\Resolver\Saga\UnresolvableSagaException;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSaga;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use stdClass;

/**
 * @internal
 */
final class DefaultSagaResolverTest extends TestCase
{
    private DefaultSagaResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new DefaultSagaResolver(
            new StackedSagaNameResolver(
                [
                    new AttributeSagaNameResolver($attributeResolver = new ReflectorAttributeResolver()),
                    new InterfaceSagaNameResolver(),
                ],
                new ClassNameSagaNameResolver(new NativeFriendlyClassNamer(new NativeInflector())),
            ),
            new AttributeSagaIdResolver($attributeResolver),
            new AttributeSagaEventSubscriberResolver($attributeResolver),
        );
    }

    #[Test]
    public function itShouldGuardThatSagaHasSagaIds(): void
    {
        self::expectException(UnresolvableSagaException::class);
        self::expectExceptionMessage('No saga ids found for saga');

        $this->resolver->resolve(stdClass::class);
    }

    #[Test]
    public function itShouldResolveSaga(): void
    {
        $definition = $this->resolver->resolve(TestSaga::class);

        self::assertEquals(new SagaDefinition(
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
        ), $definition);
    }
}

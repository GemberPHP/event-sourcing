<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Saga;

use Gember\EventSourcing\Registry\Saga\Reflector\ReflectorSagaRegistry;
use Gember\EventSourcing\Repository\Rdbms\RdbmsSagaStore;
use Gember\EventSourcing\Repository\Rdbms\SagaFactory;
use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\Interface\InterfaceDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\Stacked\StackedDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\SagaId\Attribute\AttributeSagaIdResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\DefaultDomainEventResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Attribute\AttributeEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\ClassName\ClassNameEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Interface\InterfaceEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Stacked\StackedEventNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\DefaultSagaResolver;
use Gember\EventSourcing\Resolver\Saga\Default\EventSubscriber\Attribute\AttributeSagaEventSubscriberResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Attribute\AttributeSagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\ClassName\ClassNameSagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Interface\InterfaceSagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Stacked\StackedSagaNameResolver;
use Gember\EventSourcing\Test\Repository\Rdbms\TestRdbmsSagaStoreRepository;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaEvent;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaForEventHandler;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaSecondEvent;
use Gember\EventSourcing\Test\TestDoubles\Util\Messaging\MessageBus\TestCommandBus;
use Gember\EventSourcing\Test\TestDoubles\Util\Serialization\Serializer\TestSerializer;
use Gember\EventSourcing\Test\TestDoubles\Util\Time\Clock\TestClock;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\File\Finder\Native\NativeFinder;
use Gember\EventSourcing\Util\File\Reflector\Native\NativeReflector;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Saga\SagaEventHandler;
use Override;

/**
 * @internal
 */
final class SagaEventHandlerTest extends TestCase
{
    private SagaEventHandler $handler;
    private TestRdbmsSagaStoreRepository $repository;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new SagaEventHandler(
            new DefaultDomainEventResolver(
                new StackedEventNameResolver(
                    [
                        new AttributeEventNameResolver($attributeResolver = new ReflectorAttributeResolver()),
                        new InterfaceEventNameResolver(),
                    ],
                    new ClassNameEventNameResolver(new NativeFriendlyClassNamer(new NativeInflector())),
                ),
                new StackedDomainTagResolver(
                    [
                        new AttributeDomainTagResolver($attributeResolver),
                        new InterfaceDomainTagResolver(),
                    ],
                ),
                new AttributeSagaIdResolver($attributeResolver),
            ),
            new ReflectorSagaRegistry(
                new NativeFinder(),
                new NativeReflector(),
                $sagaResolver = new DefaultSagaResolver(
                    new StackedSagaNameResolver(
                        [
                            new AttributeSagaNameResolver($attributeResolver),
                            new InterfaceSagaNameResolver(),
                        ],
                        new ClassNameSagaNameResolver(new NativeFriendlyClassNamer(new NativeInflector())),
                    ),
                    new AttributeSagaIdResolver($attributeResolver),
                    $eventSubscriberResolver = new AttributeSagaEventSubscriberResolver($attributeResolver),
                ),
                $eventSubscriberResolver,
                __DIR__ . '/../TestDoubles/Saga',
            ),
            new RdbmsSagaStore(
                $sagaResolver,
                $this->repository = new TestRdbmsSagaStoreRepository(),
                new SagaFactory(
                    $serializer = new TestSerializer(),
                ),
                $serializer,
                new TestClock(),
            ),
            new TestCommandBus(),
        );
    }

    #[Test]
    public function itShouldHandleEvent(): void
    {
        $event = new TestSagaEvent(sagaId: '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a');

        $this->handler->__invoke($event);

        // Verify saga was saved to repository
        self::assertArrayHasKey('saga.test-event-handler-3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a', $this->repository->sagas);

        $rdbmsSaga = $this->repository->sagas['saga.test-event-handler-3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a'];
        self::assertSame('saga.test-event-handler', $rdbmsSaga->sagaName);
        self::assertSame('3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a', $rdbmsSaga->sagaId);

        // Verify saga was created and method was called by deserializing the payload
        $saga = unserialize($rdbmsSaga->payload);
        self::assertInstanceOf(TestSagaForEventHandler::class, $saga);
        self::assertSame('3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a', $saga->sagaId);
        self::assertSame([TestSagaForEventHandler::class . '::onTestSagaEvent'], $saga->isCalled);
    }

    #[Test]
    public function itShouldNotHandleEventWhenSagaNotFoundAndCreationPolicyIsNever(): void
    {
        $event = new TestSagaSecondEvent(sagaId: 'afb200a7-4f94-4d40-87b2-50575a1553c7');

        $this->handler->__invoke($event);

        // Verify saga was NOT created in repository
        self::assertArrayNotHasKey('saga.test-event-handler-afb200a7-4f94-4d40-87b2-50575a1553c7', $this->repository->sagas);
        self::assertEmpty($this->repository->sagas);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Saga;

use Gember\DependencyContracts\Util\Serialization\Serializer\Serializer;
use Gember\EventSourcing\Common\CreationPolicy;
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
use Gember\EventSourcing\Saga\Default\DefaultSagaEventExecutor;
use Gember\EventSourcing\Test\Repository\Rdbms\TestRdbmsSagaStoreRepository;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaEvent;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaEventExecutor;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaForEventHandler;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaSecondEvent;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaTwoIdsEvent;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaTwoSubscribersEvent;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaWithTwoIds;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaWithTwoSubscribers;
use Gember\EventSourcing\Test\TestDoubles\Util\Messaging\MessageBus\TestCommandBus;
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
    private DefaultDomainEventResolver $domainEventResolver;
    private ReflectorSagaRegistry $sagaRegistry;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new SagaEventHandler(
            $this->domainEventResolver = new DefaultDomainEventResolver(
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
            $this->sagaRegistry = new ReflectorSagaRegistry(
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
            new DefaultSagaEventExecutor(
                new TestCommandBus(),
                new RdbmsSagaStore(
                    $sagaResolver,
                    $this->repository = new TestRdbmsSagaStoreRepository(),
                    new SagaFactory(
                        $serializer = new RoundTripSerializer(),
                    ),
                    $serializer,
                    new TestClock(),
                ),
            ),
        );
    }

    #[Test]
    public function itShouldHandleEvent(): void
    {
        $event = new TestSagaEvent(sagaId: '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a');

        $this->handler->__invoke($event);

        $rdbmsSaga = $this->repository->get('saga.test-event-handler', '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a');
        self::assertSame(['3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a'], $rdbmsSaga->sagaIds);

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

        self::assertEmpty($this->repository->sagas);
    }

    #[Test]
    public function itShouldHandleEventOnceWhenSeveralSagaIdsPointToTheSameNewSaga(): void
    {
        $this->handler->__invoke(new TestSagaTwoIdsEvent(orderId: 'order-1', paymentId: 'payment-1'));

        self::assertCount(1, $this->repository->sagas);

        $saga = $this->getTwoIdsSaga('payment-1');
        self::assertSame('order-1', $saga->orderId);
        self::assertSame(1, $saga->handled);
    }

    #[Test]
    public function itShouldHandleEventOnceWhenSeveralSagaIdsPointToTheSameExistingSaga(): void
    {
        $this->handler->__invoke(new TestSagaTwoIdsEvent(orderId: 'order-1', paymentId: 'payment-1'));
        $this->handler->__invoke(new TestSagaTwoIdsEvent(orderId: 'order-1', paymentId: 'payment-1'));

        self::assertCount(1, $this->repository->sagas);
        self::assertSame(2, $this->getTwoIdsSaga('order-1')->handled);
    }

    #[Test]
    public function itShouldNotCreateSagaForAnIdWhenAnotherIdOfTheEventPointsToAnExistingSaga(): void
    {
        // Saga owning only payment-1; the event's order-1 comes first but must not create a new saga
        $this->handler->__invoke(new TestSagaTwoIdsEvent(orderId: 'order-1', paymentId: 'payment-1'));
        $this->handler->__invoke(new TestSagaTwoIdsEvent(orderId: 'order-2', paymentId: 'payment-1'));

        self::assertCount(1, $this->repository->sagas);
        self::assertSame(2, $this->getTwoIdsSaga('payment-1')->handled);
    }

    #[Test]
    public function itShouldHandleEventByEachSagaOwningOneOfItsIds(): void
    {
        $this->handler->__invoke(new TestSagaTwoIdsEvent(orderId: 'order-1', paymentId: 'payment-1'));
        $this->handler->__invoke(new TestSagaTwoIdsEvent(orderId: 'order-2', paymentId: 'payment-2'));

        // Saga 1 owns order-1, saga 2 owns payment-2
        $this->handler->__invoke(new TestSagaTwoIdsEvent(orderId: 'order-1', paymentId: 'payment-2'));

        self::assertCount(2, $this->repository->sagas);
        self::assertSame(2, $this->getTwoIdsSaga('order-1')->handled);
        self::assertSame(2, $this->getTwoIdsSaga('payment-2')->handled);
    }

    #[Test]
    public function itShouldHandleEventByEachSubscriberMethodOfASaga(): void
    {
        $this->handler->__invoke(new TestSagaTwoSubscribersEvent(sagaId: 'saga-1'));

        $saga = unserialize($this->repository->get('saga.test-two-subscribers', 'saga-1')->payload);

        self::assertInstanceOf(TestSagaWithTwoSubscribers::class, $saga);
        self::assertSame(['onFirst', 'onSecond'], $saga->isCalled);
    }

    #[Test]
    public function itShouldExecuteOnceWithAllSagaIdsOfTheEvent(): void
    {
        $executor = new TestSagaEventExecutor();

        $handler = new SagaEventHandler($this->domainEventResolver, $this->sagaRegistry, $executor);

        $handler->__invoke(new TestSagaTwoIdsEvent(orderId: 'order-1', paymentId: 'payment-1'));

        self::assertSame([['order-1', 'payment-1']], $executor->executedSagaIdValues);
    }

    #[Test]
    public function itShouldOnlyHandleRemainingSagaIdsByExistingSagas(): void
    {
        $handledSaga = new TestSagaWithTwoIds();
        $handledSaga->orderId = 'order-1';
        $handledSaga->paymentId = 'payment-other';

        $executor = new TestSagaEventExecutor(sagasToReturn: [$handledSaga, null]);

        $handler = new SagaEventHandler($this->domainEventResolver, $this->sagaRegistry, $executor);

        $handler->__invoke(new TestSagaTwoIdsEvent(orderId: 'order-1', paymentId: 'payment-1'));

        self::assertSame([['order-1', 'payment-1'], ['payment-1']], $executor->executedSagaIdValues);
        self::assertSame(CreationPolicy::IfMissing, $executor->executedEventSubscriberDefinitions[0]->policy);
        self::assertSame(CreationPolicy::Never, $executor->executedEventSubscriberDefinitions[1]->policy);
    }

    #[Test]
    public function itShouldStopWhenHandledSagaOwnsNoneOfTheSagaIds(): void
    {
        $executor = new TestSagaEventExecutor(sagaToReturn: new TestSagaWithTwoIds());

        $handler = new SagaEventHandler($this->domainEventResolver, $this->sagaRegistry, $executor);

        $handler->__invoke(new TestSagaTwoIdsEvent(orderId: 'order-1', paymentId: 'payment-1'));

        self::assertSame(1, $executor->executeCount);
    }

    private function getTwoIdsSaga(string $sagaId): TestSagaWithTwoIds
    {
        $saga = unserialize($this->repository->get('saga.test-two-ids', $sagaId)->payload);

        self::assertInstanceOf(TestSagaWithTwoIds::class, $saga);

        return $saga;
    }
}

/**
 * @internal
 */
final readonly class RoundTripSerializer implements Serializer
{
    #[Override]
    public function serialize(object $object): string
    {
        return serialize($object);
    }

    #[Override]
    public function deserialize(string $payload, string $className): object
    {
        /** @var object */
        return unserialize($payload);
    }
}

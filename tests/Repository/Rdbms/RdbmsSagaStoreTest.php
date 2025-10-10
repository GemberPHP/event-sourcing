<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Repository\Rdbms;

use Gember\EventSourcing\Repository\Rdbms\SagaFactory;
use Gember\EventSourcing\Repository\SagaNotFoundException;
use Gember\EventSourcing\Resolver\Common\SagaId\Attribute\AttributeSagaIdResolver;
use Gember\EventSourcing\Resolver\Saga\Default\DefaultSagaResolver;
use Gember\EventSourcing\Resolver\Saga\Default\EventSubscriber\Attribute\AttributeSagaEventSubscriberResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Attribute\AttributeSagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\ClassName\ClassNameSagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Interface\InterfaceSagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Stacked\StackedSagaNameResolver;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSaga;
use Gember\EventSourcing\Test\TestDoubles\Util\Serialization\Serializer\TestSerializer;
use Gember\EventSourcing\Test\TestDoubles\Util\Time\Clock\TestClock;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Repository\Rdbms\RdbmsSagaStore;
use Override;
use stdClass;

/**
 * @internal
 */
final class RdbmsSagaStoreTest extends TestCase
{
    private RdbmsSagaStore $sagaStore;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->sagaStore = new RdbmsSagaStore(
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
            new TestRdbmsSagaStoreRepository(),
            new SagaFactory($serializer = new TestSerializer()),
            $serializer,
            new TestClock(),
        );
    }

    #[Test]
    public function itShouldThrowWhenSagaNotFound(): void
    {
        self::expectException(SagaNotFoundException::class);

        $this->sagaStore->get(TestSaga::class, 'throwing-saga-id');
    }

    #[Test]
    public function itShouldSaveAndGetSaga(): void
    {
        $this->sagaStore->save(new TestSaga('saga-id'));

        $saga = $this->sagaStore->get(TestSaga::class, 'saga-id');

        self::assertEquals(new stdClass(), $saga);
    }
}

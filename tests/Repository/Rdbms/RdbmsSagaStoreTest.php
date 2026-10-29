<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Repository\Rdbms;

use Gember\DependencyContracts\Util\Serialization\Serializer\Serializer;
use Gember\EventSourcing\Repository\Rdbms\SagaFactory;
use Gember\EventSourcing\Repository\SagaConcurrencyException;
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
    private TestRdbmsSagaStoreRepository $repository;

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
            $this->repository = new TestRdbmsSagaStoreRepository(),
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
        $this->sagaStore->save(new TestSaga('saga-id', 'another-id'));

        $saga = $this->sagaStore->get(TestSaga::class, 'saga-id');

        self::assertEquals(new stdClass(), $saga);
    }

    #[Test]
    public function itShouldSaveWithTheRevisionTheSagaIsAt(): void
    {
        $sagaStore = $this->createSagaStoreRoundTrippingSagas();

        $sagaStore->save(new TestSaga('saga-id', 'another-id'));

        $saga = $sagaStore->get(TestSaga::class, 'saga-id');
        $sagaStore->save($saga);
        $sagaStore->save($saga);

        // New saga, then the loaded revision, then the revision after its own save
        self::assertSame([null, 0, 1], $this->repository->expectedRevisions);
    }

    #[Test]
    public function itShouldThrowSagaConcurrencyExceptionWhenSagaChangedConcurrently(): void
    {
        $sagaStore = $this->createSagaStoreRoundTrippingSagas();

        $sagaStore->save(new TestSaga('saga-id', 'another-id'));

        $loadedByFirst = $sagaStore->get(TestSaga::class, 'saga-id');
        $loadedBySecond = $sagaStore->get(TestSaga::class, 'saga-id');

        $sagaStore->save($loadedByFirst);

        self::expectException(SagaConcurrencyException::class);

        $sagaStore->save($loadedBySecond);
    }

    private function createSagaStoreRoundTrippingSagas(): RdbmsSagaStore
    {
        $serializer = new class implements Serializer {
            public function serialize(object $object): string
            {
                return serialize($object);
            }

            public function deserialize(string $payload, string $className): object
            {
                /** @var object */
                return unserialize($payload);
            }
        };

        $attributeResolver = new ReflectorAttributeResolver();

        return new RdbmsSagaStore(
            new DefaultSagaResolver(
                new StackedSagaNameResolver(
                    [new AttributeSagaNameResolver($attributeResolver), new InterfaceSagaNameResolver()],
                    new ClassNameSagaNameResolver(new NativeFriendlyClassNamer(new NativeInflector())),
                ),
                new AttributeSagaIdResolver($attributeResolver),
                new AttributeSagaEventSubscriberResolver($attributeResolver),
            ),
            $this->repository,
            new SagaFactory($serializer),
            $serializer,
            new TestClock(),
        );
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Repository\Rdbms;

use Gember\DependencyContracts\EventStore\Saga\RdbmsSaga;
use Gember\EventSourcing\Repository\Rdbms\SagaFactory;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSaga;
use Gember\EventSourcing\Test\TestDoubles\Util\Serialization\Serializer\TestSerializer;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Override;
use DateTimeImmutable;
use stdClass;

/**
 * @internal
 */
final class SagaFactoryTest extends TestCase
{
    private SagaFactory $factory;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new SagaFactory(
            new TestSerializer(),
        );
    }

    #[Test]
    public function itShouldCreateFromRdbmsSaga(): void
    {
        $saga = $this->factory->createFromRdbmsSaga(
            TestSaga::class,
            new RdbmsSaga(
                'saga.test',
                'a7a0fa85-32cc-49ea-aa54-12ff609fc43b',
                '{}',
                new DateTimeImmutable(),
                null,
            ),
        );

        self::assertEquals(new stdClass(), $saga);
    }
}

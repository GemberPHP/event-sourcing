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
                '08e5d180-20d7-4f01-885c-c7e997fab31d',
                'saga.test',
                ['f400964e-1381-4e0e-85b3-14121e4af730', '9a1035c0-7e87-4eba-a4bb-db809423e6be'],
                '{}',
                new DateTimeImmutable(),
                null,
            ),
        );

        self::assertEquals(new stdClass(), $saga);
    }
}

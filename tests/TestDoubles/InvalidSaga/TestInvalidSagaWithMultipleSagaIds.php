<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\InvalidSaga;

use Gember\EventSourcing\Saga\Attribute\SagaId;

final class TestInvalidSagaWithMultipleSagaIds
{
    #[SagaId]
    public string $id;

    #[SagaId]
    public string $anotherId;
}

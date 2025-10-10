<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\InvalidSaga;

use Gember\EventSourcing\Saga\Attribute\SagaId;

final readonly class TestInvalidSagaWithPrivateSagaId
{
    #[SagaId]
    private string $id;
}

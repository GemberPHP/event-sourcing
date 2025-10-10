<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Saga;

use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\Saga\NamedSaga;

final class TestSagaWithNamedInterface implements NamedSaga
{
    #[SagaId]
    private string $someId;

    public static function getName(): string
    {
        return 'saga.test-named';
    }
}

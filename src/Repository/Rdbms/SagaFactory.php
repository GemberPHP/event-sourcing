<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository\Rdbms;

use Gember\DependencyContracts\EventStore\Saga\RdbmsSaga;
use Gember\DependencyContracts\Util\Serialization\Serializer\Serializer;

final readonly class SagaFactory
{
    public function __construct(
        private Serializer $serializer,
    ) {}

    /**
     * @param class-string $sagaClassName
     */
    public function createFromRdbmsSaga(string $sagaClassName, RdbmsSaga $rdbmsSaga): object
    {
        return $this->serializer->deserialize($rdbmsSaga->payload, $sagaClassName);
    }
}

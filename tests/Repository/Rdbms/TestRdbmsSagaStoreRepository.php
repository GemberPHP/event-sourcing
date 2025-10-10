<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Repository\Rdbms;

use DateTimeImmutable;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSaga;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaNotFoundException;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaStoreRepository;
use Stringable;

final class TestRdbmsSagaStoreRepository implements RdbmsSagaStoreRepository
{
    /**
     * @var array<string, RdbmsSaga>
     */
    public array $sagas = [];

    public function get(string $sagaName, Stringable|string $sagaId): RdbmsSaga
    {
        return $this->sagas[sprintf('%s-%s', $sagaName, $sagaId)] ?? throw RdbmsSagaNotFoundException::withSagaId($sagaName, $sagaId);
    }

    public function save(string $sagaName, Stringable|string $sagaId, string $payload, DateTimeImmutable $now): RdbmsSaga
    {
        $rdbmsSaga = new RdbmsSaga(
            $sagaName,
            $sagaId,
            $payload,
            $now,
            null,
        );

        $this->sagas[sprintf('%s-%s', $sagaName, $sagaId)] = $rdbmsSaga;

        return $rdbmsSaga;
    }
}

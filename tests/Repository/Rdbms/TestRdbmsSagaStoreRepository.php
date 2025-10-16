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

    public function get(string $sagaName, Stringable|string ...$sagaIds): RdbmsSaga
    {
        foreach ($sagaIds as $sagaId) {
            if (isset($this->sagas[sprintf('%s-%s', $sagaName, $sagaId)])) {
                return $this->sagas[sprintf('%s-%s', $sagaName, $sagaId)];
            }
        }

        throw RdbmsSagaNotFoundException::create($sagaName, ...$sagaIds);
    }

    public function save(string $sagaName, string $payload, DateTimeImmutable $now, Stringable|string ...$sagaIds): RdbmsSaga
    {
        $rdbmsSaga = new RdbmsSaga(
            '',
            $sagaName,
            array_values($sagaIds),
            $payload,
            $now,
            null,
        );

        foreach ($sagaIds as $sagaId) {
            $this->sagas[sprintf('%s-%s', $sagaName, $sagaId)] = $rdbmsSaga;
        }

        return $rdbmsSaga;
    }
}

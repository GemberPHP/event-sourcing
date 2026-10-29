<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Repository\Rdbms;

use DateTimeImmutable;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSaga;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaConcurrencyException;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaNotFoundException;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaStoreRepository;
use Stringable;

final class TestRdbmsSagaStoreRepository implements RdbmsSagaStoreRepository
{
    /**
     * @var array<string, RdbmsSaga>
     */
    public array $sagas = [];

    /**
     * @var list<int|null>
     */
    public array $expectedRevisions = [];

    public function get(string $sagaName, Stringable|string ...$sagaIds): RdbmsSaga
    {
        foreach ($sagaIds as $sagaId) {
            if (isset($this->sagas[sprintf('%s-%s', $sagaName, $sagaId)])) {
                return $this->sagas[sprintf('%s-%s', $sagaName, $sagaId)];
            }
        }

        throw RdbmsSagaNotFoundException::create($sagaName, ...$sagaIds);
    }

    public function save(
        string $sagaName,
        string $payload,
        DateTimeImmutable $now,
        ?int $expectedRevision,
        Stringable|string ...$sagaIds,
    ): RdbmsSaga {
        $this->expectedRevisions[] = $expectedRevision;

        foreach ($sagaIds as $sagaId) {
            $current = $this->sagas[sprintf('%s-%s', $sagaName, $sagaId)] ?? null;

            if ($current !== null && $current->revision !== $expectedRevision) {
                throw RdbmsSagaConcurrencyException::create();
            }
        }

        $rdbmsSaga = new RdbmsSaga(
            '',
            $sagaName,
            array_values($sagaIds),
            $payload,
            $now,
            null,
            $expectedRevision === null ? 0 : $expectedRevision + 1,
        );

        foreach ($sagaIds as $sagaId) {
            $this->sagas[sprintf('%s-%s', $sagaName, $sagaId)] = $rdbmsSaga;
        }

        return $rdbmsSaga;
    }
}

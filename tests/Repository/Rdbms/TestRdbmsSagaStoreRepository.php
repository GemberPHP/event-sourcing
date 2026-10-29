<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Repository\Rdbms;

use DateTimeImmutable;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSaga;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaConcurrencyException;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaIdAlreadyInUseException;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaNotFoundException;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaStoreRepository;
use Stringable;

/**
 * In-memory equivalent of the RDBMS saga store: an update compares and sets the revision of the saga it was loaded
 * from, and a saga id belongs to at most one saga per saga name.
 */
final class TestRdbmsSagaStoreRepository implements RdbmsSagaStoreRepository
{
    /**
     * @var array<string, RdbmsSaga>
     */
    public array $sagas = [];

    /**
     * @var list<?RdbmsSaga>
     */
    public array $previousSagas = [];

    private int $nextId = 1;

    public function get(string $sagaName, Stringable|string ...$sagaIds): RdbmsSaga
    {
        foreach ($sagaIds as $sagaId) {
            foreach ($this->sagas as $saga) {
                if ($saga->sagaName === $sagaName && in_array((string) $sagaId, array_map(strval(...), $saga->sagaIds), true)) {
                    return $saga;
                }
            }
        }

        throw RdbmsSagaNotFoundException::create($sagaName, ...$sagaIds);
    }

    public function save(
        string $sagaName,
        string $payload,
        DateTimeImmutable $now,
        ?RdbmsSaga $previous,
        Stringable|string ...$sagaIds,
    ): RdbmsSaga {
        $this->previousSagas[] = $previous;

        if ($previous !== null && ($this->sagas[$previous->id] ?? null)?->revision !== $previous->revision) {
            throw RdbmsSagaConcurrencyException::create();
        }

        foreach ($sagaIds as $sagaId) {
            foreach ($this->sagas as $saga) {
                if ($saga->sagaName === $sagaName
                    && $saga->id !== $previous?->id
                    && in_array((string) $sagaId, array_map(strval(...), $saga->sagaIds), true)) {
                    throw RdbmsSagaIdAlreadyInUseException::create($sagaName, $sagaId);
                }
            }
        }

        $rdbmsSaga = new RdbmsSaga(
            $previous->id ?? (string) $this->nextId++,
            $sagaName,
            array_values($sagaIds),
            $payload,
            $previous->createdAt ?? $now,
            $previous !== null ? $now : null,
            $previous !== null ? $previous->revision + 1 : 0,
        );

        $this->sagas[$rdbmsSaga->id] = $rdbmsSaga;

        return $rdbmsSaga;
    }
}

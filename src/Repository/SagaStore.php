<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Stringable;

interface SagaStore
{
    /**
     * Gets the saga that owns any of the given saga ids. A saga id belongs to at most one saga per saga name.
     *
     * @param class-string $sagaClassName
     *
     * @throws SagaNotFoundException
     */
    public function get(string $sagaClassName, string|Stringable ...$sagaIds): object;

    /**
     * Saves a saga that was created or loaded (gotten) by this store.
     *
     * @throws SagaConcurrencyException when the saga was changed or created concurrently: reload and retry
     * @throws SagaIdAlreadyInUseException when a saga id already belongs to another saga: not retryable
     */
    public function save(object $saga): void;
}

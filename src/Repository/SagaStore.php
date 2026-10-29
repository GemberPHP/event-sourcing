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
     * Saves a saga. A saga instance gotten from this store is saved as an update of it; any other instance (a new
     * saga, but also a clone or unserialized copy) is saved as a new saga.
     *
     * @throws SagaConcurrencyException when the saga was changed concurrently, or a saga id of a new saga is taken
     *                                  (created concurrently): reload and retry
     * @throws SagaIdAlreadyInUseException when a saga id added to an existing saga already belongs to another saga:
     *                                     not retryable
     */
    public function save(object $saga): void;
}

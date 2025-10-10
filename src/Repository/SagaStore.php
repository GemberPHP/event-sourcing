<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Stringable;

interface SagaStore
{
    /**
     * @param class-string $sagaClassName
     *
     * @throws SagaNotFoundException
     */
    public function get(string $sagaClassName, string|Stringable $sagaId): object;

    public function save(object $saga): void;
}

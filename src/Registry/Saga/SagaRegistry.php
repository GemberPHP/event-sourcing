<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\Saga;

use Gember\EventSourcing\Resolver\Saga\SagaDefinition;

/**
 * Retrieve saga definition based on normalized saga id name.
 */
interface SagaRegistry
{
    /**
     * @throws SagaNotRegisteredException
     *
     * @return list<SagaDefinition>
     */
    public function retrieve(string $sagaIdName): array;
}

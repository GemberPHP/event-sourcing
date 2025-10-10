<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga\Default\SagaName;

/**
 * Resolve normalized saga name from a Saga.
 * The saga name is used to identify the saga in the repository (persisted).
 */
interface SagaNameResolver
{
    /**
     * @param class-string $sagaClassName
     *
     * @throws UnresolvableSagaNameException
     */
    public function resolve(string $sagaClassName): string;
}

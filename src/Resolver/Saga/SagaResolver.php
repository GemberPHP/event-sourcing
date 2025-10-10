<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga;

interface SagaResolver
{
    /**
     * @param class-string $sagaClassName
     *
     * @throws UnresolvableSagaException
     */
    public function resolve(string $sagaClassName): SagaDefinition;
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Common\SagaId;

interface SagaIdResolver
{
    /**
     * @param class-string $className
     *
     * @return list<SagaIdDefinition>
     */
    public function resolve(string $className): array;
}

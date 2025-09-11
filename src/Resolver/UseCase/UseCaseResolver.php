<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase;

interface UseCaseResolver
{
    /**
     * @param class-string $useCaseClassName
     */
    public function resolve(string $useCaseClassName): UseCaseDefinition;
}

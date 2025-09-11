<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase;

use Gember\EventSourcing\Resolver\UseCase\UseCaseDefinition;
use Gember\EventSourcing\Resolver\UseCase\UseCaseResolver;

final class UseCaseAttributeRegistry
{
    private static UseCaseResolver $useCaseResolver;

    public static function initialize(
        UseCaseResolver $useCaseResolver,
    ): void {
        self::$useCaseResolver = $useCaseResolver;
    }

    /**
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     */
    public static function getUseCaseDefinition(string $useCaseClassName): UseCaseDefinition
    {
        return self::$useCaseResolver->resolve($useCaseClassName);
    }
}

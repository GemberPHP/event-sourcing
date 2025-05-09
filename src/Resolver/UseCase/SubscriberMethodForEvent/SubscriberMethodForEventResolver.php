<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\SubscriberMethodForEvent;

use Gember\EventSourcing\UseCase\EventSourcedUseCase;

/**
 * Resolves the subscribing method name (if present) for a domain event within a use case.
 */
interface SubscriberMethodForEventResolver
{
    /**
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     * @param class-string $eventClassName
     */
    public function resolve(
        string $useCaseClassName,
        string $eventClassName,
    ): ?string;
}

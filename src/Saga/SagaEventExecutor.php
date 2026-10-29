<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga;

use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Stringable;

interface SagaEventExecutor
{
    /**
     * @param class-string $sagaClassName
     *
     * @return object|null The saga after handling the event, or null when no saga handled it
     */
    public function execute(
        object $event,
        SagaEventSubscriberDefinition $eventSubscriberDefinition,
        string $sagaClassName,
        string $methodName,
        string|Stringable $sagaIdValue,
    ): ?object;
}

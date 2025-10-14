<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga;

use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Stringable;

interface SagaEventExecutor
{
    /**
     * @param class-string $sagaClassName
     */
    public function execute(
        object $event,
        SagaEventSubscriberDefinition $eventSubscriberDefinition,
        string $sagaClassName,
        string $methodName,
        string|Stringable $sagaIdValue,
    ): void;
}

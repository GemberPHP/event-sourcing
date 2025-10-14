<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Saga;

use Exception;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\SagaEventExecutor;
use Override;
use Stringable;

/**
 * @internal
 */
final class TestSagaEventExecutor implements SagaEventExecutor
{
    public function __construct(
        public bool $wasExecuted = false,
        public ?object $lastEvent = null,
        public ?SagaEventSubscriberDefinition $lastEventSubscriberDefinition = null,
        public ?string $lastSagaClassName = null,
        public ?string $lastMethodName = null,
        public string|Stringable|null $lastSagaIdValue = null,
        public ?Exception $shouldThrow = null,
    ) {}

    #[Override]
    public function execute(
        object $event,
        SagaEventSubscriberDefinition $eventSubscriberDefinition,
        string $sagaClassName,
        string $methodName,
        string|Stringable $sagaIdValue,
    ): void {
        $this->wasExecuted = true;
        $this->lastEvent = $event;
        $this->lastEventSubscriberDefinition = $eventSubscriberDefinition;
        $this->lastSagaClassName = $sagaClassName;
        $this->lastMethodName = $methodName;
        $this->lastSagaIdValue = $sagaIdValue;

        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }
    }
}

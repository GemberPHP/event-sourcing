<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Saga;

use Exception;
use LogicException;
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
        /** @var list<string|Stringable> */
        public array $lastSagaIdValues = [],
        public ?Exception $shouldThrow = null,
        public ?object $sagaToReturn = null,
        /** @var list<?object> Returned per execution, in order; falls back to $sagaToReturn when exhausted */
        public array $sagasToReturn = [],
        public int $executeCount = 0,
        /** @var list<list<string>> */
        public array $executedSagaIdValues = [],
        /** @var list<SagaEventSubscriberDefinition> */
        public array $executedEventSubscriberDefinitions = [],
    ) {}

    #[Override]
    public function execute(
        object $event,
        SagaEventSubscriberDefinition $eventSubscriberDefinition,
        string $sagaClassName,
        string $methodName,
        string|Stringable ...$sagaIdValues,
    ): ?object {
        $this->wasExecuted = true;
        ++$this->executeCount;

        // Fail a test that would otherwise never end (e.g. a handler looping without progress)
        if ($this->executeCount > 100) {
            throw new LogicException('Executed more than 100 times');
        }
        $this->executedSagaIdValues[] = array_values(array_map(strval(...), $sagaIdValues));
        $this->executedEventSubscriberDefinitions[] = $eventSubscriberDefinition;
        $this->lastEvent = $event;
        $this->lastEventSubscriberDefinition = $eventSubscriberDefinition;
        $this->lastSagaClassName = $sagaClassName;
        $this->lastMethodName = $methodName;
        $this->lastSagaIdValues = array_values($sagaIdValues);

        if ($this->shouldThrow !== null) {
            throw $this->shouldThrow;
        }

        if ($this->sagasToReturn !== []) {
            return array_shift($this->sagasToReturn);
        }

        return $this->sagaToReturn;
    }
}

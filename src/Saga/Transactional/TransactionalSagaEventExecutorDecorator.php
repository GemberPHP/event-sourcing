<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga\Transactional;

use Gember\DependencyContracts\Util\Transaction\Transactional;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\SagaEventExecutor;
use Override;
use Stringable;

final readonly class TransactionalSagaEventExecutorDecorator implements SagaEventExecutor
{
    public function __construct(
        private SagaEventExecutor $sagaEventExecutor,
        private Transactional $transactional,
    ) {}

    #[Override]
    public function execute(
        object $event,
        SagaEventSubscriberDefinition $eventSubscriberDefinition,
        string $sagaClassName,
        string $methodName,
        string|Stringable $sagaIdValue,
    ): void {
        $this->transactional->transactional(
            fn() => $this->sagaEventExecutor->execute($event, $eventSubscriberDefinition, $sagaClassName, $methodName, $sagaIdValue),
        );
    }
}

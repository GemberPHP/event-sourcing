<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga\Default;

use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Repository\SagaNotFoundException;
use Gember\EventSourcing\Repository\SagaStore;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\SagaEventExecutor;
use Override;
use Stringable;

final readonly class DefaultSagaEventExecutor implements SagaEventExecutor
{
    public function __construct(
        private CommandBus $commandBus,
        private SagaStore $sagaStore,
    ) {}

    #[Override]
    public function execute(
        object $event,
        SagaEventSubscriberDefinition $eventSubscriberDefinition,
        string $sagaClassName,
        string $methodName,
        string|Stringable $sagaIdValue,
    ): void {
        // Get persisted Saga by saga id from repository
        try {
            $saga = $this->sagaStore->get($sagaClassName, $sagaIdValue);
        } catch (SagaNotFoundException) {
            if ($eventSubscriberDefinition->policy !== CreationPolicy::IfMissing) {
                return;
            }

            $saga = new $sagaClassName();
        }

        // Run saga
        $saga->{$methodName}($event, $this->commandBus);

        // Save saga in repository
        $this->sagaStore->save($saga);
    }
}

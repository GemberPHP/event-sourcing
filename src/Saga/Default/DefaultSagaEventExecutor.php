<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga\Default;

use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Repository\SagaConcurrencyException;
use Gember\EventSourcing\Repository\SagaNotFoundException;
use Gember\EventSourcing\Repository\SagaStore;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\CommandRecorder;
use Gember\EventSourcing\Saga\SagaCommandDispatchFailedException;
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
        string|Stringable ...$sagaIdValues,
    ): ?object {
        // Get persisted Saga by saga id from repository
        try {
            $saga = $this->sagaStore->get($sagaClassName, ...$sagaIdValues);
        } catch (SagaNotFoundException) {
            if ($eventSubscriberDefinition->policy !== CreationPolicy::IfMissing) {
                return null;
            }

            $saga = new $sagaClassName();
        }

        // Run saga with command recorder to defer command dispatching until after save
        $commandRecorder = new CommandRecorder($this->commandBus);

        $saga->{$methodName}($event, $commandRecorder);

        // Save saga in repository
        $this->sagaStore->save($saga);

        // Dispatch recorded commands after saga state is persisted
        try {
            $commandRecorder->flush();
        } catch (SagaConcurrencyException $exception) {
            /*
             * Another saga, handling a dispatched command's events synchronously, ran out of retries. Without a
             * transaction this saga is saved already: retrying it would dispatch its commands again. Inside a
             * transaction its save is rolled back with it, and only the transaction's owner can retry.
             */
            throw SagaCommandDispatchFailedException::create($sagaClassName, $exception);
        }

        return $saga;
    }
}

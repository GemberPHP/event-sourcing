<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga\Retrying;

use Gember\EventSourcing\Repository\SagaConcurrencyException;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\SagaEventExecutor;
use Override;
use Stringable;

/**
 * Retries handling an event when the saga was changed concurrently: reloads the saga, runs the handler again and
 * saves. Safe, as recorded commands are only dispatched after a successful save (and roll back with it in a
 * transaction). Decorates the outermost executor, so each attempt runs in its own transaction.
 */
final readonly class RetryingSagaEventExecutorDecorator implements SagaEventExecutor
{
    private const int MAX_ATTEMPTS = 3;

    public function __construct(
        private SagaEventExecutor $sagaEventExecutor,
    ) {}

    #[Override]
    public function execute(
        object $event,
        SagaEventSubscriberDefinition $eventSubscriberDefinition,
        string $sagaClassName,
        string $methodName,
        string|Stringable $sagaIdValue,
    ): ?object {
        for ($attempt = 1;; ++$attempt) {
            try {
                return $this->sagaEventExecutor->execute($event, $eventSubscriberDefinition, $sagaClassName, $methodName, $sagaIdValue);
            } catch (SagaConcurrencyException $exception) {
                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }
}

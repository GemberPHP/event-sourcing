<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga\Retrying;

use Gember\DependencyContracts\Util\Transaction\Transactional;
use Gember\EventSourcing\Repository\SagaConcurrencyException;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\SagaEventExecutor;
use Gember\EventSourcing\Util\String\ClassNameSegmentHelper;
use Override;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Retries handling an event when the saga was changed concurrently: reloads the saga, runs the handler again and
 * saves, with a jittered exponential backoff. Safe, as recorded commands are only dispatched after a successful save
 * (and roll back with it in a transaction). Decorates the outermost executor, so each attempt runs in its own
 * transaction.
 *
 * Inside a transaction it didn't open (e.g. one around the command handler), it doesn't retry: that transaction
 * still holds the failed attempt's writes and reads from an old snapshot, so only its owner can retry the whole unit.
 */
final readonly class RetryingSagaEventExecutorDecorator implements SagaEventExecutor
{
    private const int MAX_ATTEMPTS = 5;
    private const int BACKOFF_BASE_MICROSECONDS = 5_000;

    public function __construct(
        private SagaEventExecutor $sagaEventExecutor,
        private Transactional $transactional,
        private ?LoggerInterface $logger = null,
    ) {}

    #[Override]
    public function execute(
        object $event,
        SagaEventSubscriberDefinition $eventSubscriberDefinition,
        string $sagaClassName,
        string $methodName,
        string|Stringable ...$sagaIdValues,
    ): ?object {
        $maxAttempts = $this->transactional->isActive() ? 1 : self::MAX_ATTEMPTS;

        for ($attempt = 1;; ++$attempt) {
            try {
                return $this->sagaEventExecutor->execute($event, $eventSubscriberDefinition, $sagaClassName, $methodName, ...$sagaIdValues);
            } catch (SagaConcurrencyException $exception) {
                if ($attempt >= $maxAttempts) {
                    $this->logger?->warning(sprintf(
                        '[Saga] Gave up handling %s by %s after %d attempt(s): saga was changed concurrently',
                        ClassNameSegmentHelper::getLastSegment($event::class),
                        ClassNameSegmentHelper::getLastSegment($sagaClassName),
                        $attempt,
                    ), [
                        'sagaIds' => array_map(strval(...), $sagaIdValues),
                        'insideTransaction' => $maxAttempts === 1,
                    ]);

                    throw $exception;
                }

                // Jittered exponential backoff, so concurrent handlers of the same saga don't collide again
                usleep(random_int(0, self::BACKOFF_BASE_MICROSECONDS * 2 ** ($attempt - 1)));
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga\Loggable;

use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\SagaEventExecutor;
use Gember\EventSourcing\Util\String\ClassNameSegmentHelper;
use Override;
use Psr\Log\LoggerInterface;
use Throwable;
use Stringable;

final readonly class LoggableSagaEventExecutorDecorator implements SagaEventExecutor
{
    public function __construct(
        private SagaEventExecutor $sagaEventExecutor,
        private LoggerInterface $logger,
    ) {}

    #[Override]
    public function execute(
        object $event,
        SagaEventSubscriberDefinition $eventSubscriberDefinition,
        string $sagaClassName,
        string $methodName,
        string|Stringable $sagaIdValue,
    ): void {
        $startTime = microtime(true);

        $this->logger->info(sprintf(
            '[Saga] Started handling %s by %s',
            ClassNameSegmentHelper::getLastSegment($event::class),
            ClassNameSegmentHelper::getLastSegment($sagaClassName),
        ), [
            'sagaId' => (string) $sagaIdValue,
        ]);

        try {
            $this->sagaEventExecutor->execute($event, $eventSubscriberDefinition, $sagaClassName, $methodName, $sagaIdValue);
        } catch (Throwable $exception) {
            $this->logger->info(sprintf(
                '[Saga] Failed handling %s by %s',
                ClassNameSegmentHelper::getLastSegment($event::class),
                ClassNameSegmentHelper::getLastSegment($sagaClassName),
            ), [
                'exception' => $exception->getMessage(),
                'exceptionClass' => $exception::class,
                'sagaId' => (string) $sagaIdValue,
                'duration' => microtime(true) - $startTime,
            ]);

            throw $exception;
        }

        $this->logger->info(sprintf(
            '[Saga] Finished handling %s by %s',
            ClassNameSegmentHelper::getLastSegment($event::class),
            ClassNameSegmentHelper::getLastSegment($sagaClassName),
        ), [
            'sagaId' => (string) $sagaIdValue,
            'duration' => microtime(true) - $startTime,
        ]);
    }
}

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
        string|Stringable ...$sagaIdValues,
    ): ?object {
        $startTime = microtime(true);

        $this->logger->info(sprintf(
            '[Saga] Started handling %s by %s',
            ClassNameSegmentHelper::getLastSegment($event::class),
            ClassNameSegmentHelper::getLastSegment($sagaClassName),
        ), [
            'sagaIds' => array_map(strval(...), $sagaIdValues),
        ]);

        try {
            $saga = $this->sagaEventExecutor->execute($event, $eventSubscriberDefinition, $sagaClassName, $methodName, ...$sagaIdValues);
        } catch (Throwable $exception) {
            $this->logger->info(sprintf(
                '[Saga] Failed handling %s by %s',
                ClassNameSegmentHelper::getLastSegment($event::class),
                ClassNameSegmentHelper::getLastSegment($sagaClassName),
            ), [
                'exception' => $exception->getMessage(),
                'exceptionClass' => $exception::class,
                'sagaIds' => array_map(strval(...), $sagaIdValues),
                'duration' => microtime(true) - $startTime,
            ]);

            throw $exception;
        }

        $this->logger->info(sprintf(
            '[Saga] Finished handling %s by %s',
            ClassNameSegmentHelper::getLastSegment($event::class),
            ClassNameSegmentHelper::getLastSegment($sagaClassName),
        ), [
            'sagaIds' => array_map(strval(...), $sagaIdValues),
            'duration' => microtime(true) - $startTime,
        ]);

        return $saga;
    }
}

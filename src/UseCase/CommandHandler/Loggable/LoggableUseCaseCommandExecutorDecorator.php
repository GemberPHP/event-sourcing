<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase\CommandHandler\Loggable;

use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\UseCase\CommandHandler\UseCaseCommandExecutor;
use Gember\EventSourcing\Util\String\ClassNameSegmentHelper;
use Psr\Log\LoggerInterface;
use Stringable;
use Throwable;

final readonly class LoggableUseCaseCommandExecutorDecorator implements UseCaseCommandExecutor
{
    public function __construct(
        private UseCaseCommandExecutor $useCaseCommandExecutor,
        private LoggerInterface $logger,
    ) {}

    public function execute(
        object $command,
        CommandHandlerDefinition $commandHandlerDefinition,
        string $useCaseClassName,
        string $methodName,
        string|Stringable ...$domainTags,
    ): void {
        $startTime = microtime(true);

        $this->logger->info(sprintf(
            '[UseCase] Started handling %s by %s',
            ClassNameSegmentHelper::getLastSegment($command::class),
            ClassNameSegmentHelper::getLastSegment($useCaseClassName),
        ), [
            'domainTags' => array_map(fn($domainTag) => (string) $domainTag, $domainTags),
        ]);

        try {
            $this->useCaseCommandExecutor->execute($command, $commandHandlerDefinition, $useCaseClassName, $methodName, ...$domainTags);
        } catch (Throwable $exception) {
            $this->logger->info(sprintf(
                '[UseCase] Failed handling %s by %s',
                ClassNameSegmentHelper::getLastSegment($command::class),
                ClassNameSegmentHelper::getLastSegment($useCaseClassName),
            ), [
                'exception' => $exception->getMessage(),
                'exceptionClass' => $exception::class,
                'domainTags' => array_map(fn($domainTag) => (string) $domainTag, $domainTags),
                'duration' => microtime(true) - $startTime,
            ]);

            throw $exception;
        }

        $this->logger->info(sprintf(
            '[UseCase] Finished handling %s by %s',
            ClassNameSegmentHelper::getLastSegment($command::class),
            ClassNameSegmentHelper::getLastSegment($useCaseClassName),
        ), [
            'domainTags' => array_map(fn($domainTag) => (string) $domainTag, $domainTags),
            'duration' => microtime(true) - $startTime,
        ]);
    }
}

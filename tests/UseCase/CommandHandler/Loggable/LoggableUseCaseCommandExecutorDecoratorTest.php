<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\UseCase\CommandHandler\Loggable;

use Exception;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestFullUseCase;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCommandExecutor;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\Util\Log\TestLogger;
use Gember\EventSourcing\UseCase\CommandHandler\Loggable\LoggableUseCaseCommandExecutorDecorator;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stringable;

/**
 * @internal
 */
final class LoggableUseCaseCommandExecutorDecoratorTest extends TestCase
{
    private TestUseCaseCommandExecutor $innerExecutor;
    private TestLogger $logger;
    private LoggableUseCaseCommandExecutorDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerExecutor = new TestUseCaseCommandExecutor();
        $this->logger = new TestLogger();
        $this->decorator = new LoggableUseCaseCommandExecutorDecorator(
            $this->innerExecutor,
            $this->logger,
        );
    }

    #[Test]
    public function itShouldLogSuccessfulExecution(): void
    {
        $command = new TestUseCaseWithCommand(domainTag: 'test-domain-tag');
        $commandHandlerDefinition = new CommandHandlerDefinition(
            TestUseCaseWithCommand::class,
            'handle',
            CreationPolicy::IfMissing,
        );
        $useCaseClassName = TestFullUseCase::class;
        $methodName = 'handle';
        $domainTag1 = new class implements Stringable {
            #[Override]
            public function __toString(): string
            {
                return 'domain-tag-1';
            }
        };
        $domainTag2 = 'domain-tag-2';
        $domainTag3 = 'domain-tag-3';

        $this->decorator->execute($command, $commandHandlerDefinition, $useCaseClassName, $methodName, $domainTag1, $domainTag2, $domainTag3);

        self::assertTrue($this->innerExecutor->wasExecuted);
        self::assertSame($command, $this->innerExecutor->lastCommand);
        self::assertSame($commandHandlerDefinition, $this->innerExecutor->lastCommandHandlerDefinition);
        self::assertSame($useCaseClassName, $this->innerExecutor->lastUseCaseClassName);
        self::assertSame($methodName, $this->innerExecutor->lastMethodName);
        self::assertSame([$domainTag1, $domainTag2, $domainTag3], $this->innerExecutor->lastDomainTags);

        self::assertCount(2, $this->logger->logs);
        self::assertSame('[UseCase] Started handling TestUseCaseWithCommand by TestFullUseCase', $this->logger->logs[0]['message']);
        self::assertSame(['domainTags' => ['domain-tag-1', 'domain-tag-2', 'domain-tag-3']], $this->logger->logs[0]['context']);

        self::assertSame('[UseCase] Finished handling TestUseCaseWithCommand by TestFullUseCase', $this->logger->logs[1]['message']);
        self::assertSame(['domain-tag-1', 'domain-tag-2', 'domain-tag-3'], $this->logger->logs[1]['context']['domainTags']);
    }

    #[Test]
    public function itShouldLogFailedExecution(): void
    {
        $command = new TestUseCaseWithCommand(domainTag: 'test-domain-tag');
        $commandHandlerDefinition = new CommandHandlerDefinition(
            TestUseCaseWithCommand::class,
            'handle',
            CreationPolicy::IfMissing,
        );
        $useCaseClassName = TestFullUseCase::class;
        $methodName = 'handle';
        $domainTag = 'domain-tag-123';

        $exception = new Exception('UseCase execution failed');
        $this->innerExecutor->shouldThrow = $exception;

        try {
            $this->decorator->execute($command, $commandHandlerDefinition, $useCaseClassName, $methodName, $domainTag);
        } catch (Exception) {
            self::assertTrue($this->innerExecutor->wasExecuted);

            self::assertCount(2, $this->logger->logs);
            self::assertSame('[UseCase] Started handling TestUseCaseWithCommand by TestFullUseCase', $this->logger->logs[0]['message']);
            self::assertSame(['domainTags' => ['domain-tag-123']], $this->logger->logs[0]['context']);

            self::assertSame('[UseCase] Failed handling TestUseCaseWithCommand by TestFullUseCase', $this->logger->logs[1]['message']);
            self::assertSame('UseCase execution failed', $this->logger->logs[1]['context']['exception']);
            self::assertSame(Exception::class, $this->logger->logs[1]['context']['exceptionClass']);
            self::assertSame(['domain-tag-123'], $this->logger->logs[1]['context']['domainTags']);
        }
    }
}

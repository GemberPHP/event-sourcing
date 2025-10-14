<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Saga\Loggable;

use Exception;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\Loggable\LoggableSagaEventExecutorDecorator;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaEvent;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaEventExecutor;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaForEventHandler;
use Gember\EventSourcing\Test\TestDoubles\Util\Log\TestLogger;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stringable;

/**
 * @internal
 */
final class LoggableSagaEventExecutorDecoratorTest extends TestCase
{
    private TestSagaEventExecutor $innerExecutor;
    private TestLogger $logger;
    private LoggableSagaEventExecutorDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerExecutor = new TestSagaEventExecutor();
        $this->logger = new TestLogger();
        $this->decorator = new LoggableSagaEventExecutorDecorator(
            $this->innerExecutor,
            $this->logger,
        );
    }

    #[Test]
    public function itShouldLogSuccessfulExecution(): void
    {
        $event = new TestSagaEvent(sagaId: '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a');
        $eventSubscriberDefinition = new SagaEventSubscriberDefinition(
            TestSagaEvent::class,
            'onTestSagaEvent',
            CreationPolicy::IfMissing,
        );
        $sagaClassName = TestSagaForEventHandler::class;
        $methodName = 'onTestSagaEvent';
        $sagaIdValue = new class implements Stringable {
            #[Override]
            public function __toString(): string
            {
                return '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a';
            }
        };

        $this->decorator->execute($event, $eventSubscriberDefinition, $sagaClassName, $methodName, $sagaIdValue);

        self::assertTrue($this->innerExecutor->wasExecuted);
        self::assertSame($event, $this->innerExecutor->lastEvent);
        self::assertSame($eventSubscriberDefinition, $this->innerExecutor->lastEventSubscriberDefinition);
        self::assertSame($sagaClassName, $this->innerExecutor->lastSagaClassName);
        self::assertSame($methodName, $this->innerExecutor->lastMethodName);
        self::assertSame($sagaIdValue, $this->innerExecutor->lastSagaIdValue);

        self::assertCount(2, $this->logger->logs);
        self::assertSame('[Saga] Started handling TestSagaEvent by TestSagaForEventHandler', $this->logger->logs[0]['message']);
        self::assertSame(['sagaId' => '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a'], $this->logger->logs[0]['context']);

        self::assertSame('[Saga] Finished handling TestSagaEvent by TestSagaForEventHandler', $this->logger->logs[1]['message']);
        self::assertSame('3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a', $this->logger->logs[1]['context']['sagaId']);
    }

    #[Test]
    public function itShouldLogFailedExecution(): void
    {
        $event = new TestSagaEvent(sagaId: '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a');
        $eventSubscriberDefinition = new SagaEventSubscriberDefinition(
            TestSagaEvent::class,
            'onTestSagaEvent',
            CreationPolicy::IfMissing,
        );
        $sagaClassName = TestSagaForEventHandler::class;
        $methodName = 'onTestSagaEvent';
        $sagaIdValue = '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a';

        $exception = new Exception('Saga execution failed');
        $this->innerExecutor->shouldThrow = $exception;

        try {
            $this->decorator->execute($event, $eventSubscriberDefinition, $sagaClassName, $methodName, $sagaIdValue);
        } catch (Exception) {
            self::assertTrue($this->innerExecutor->wasExecuted);

            self::assertCount(2, $this->logger->logs);
            self::assertSame('[Saga] Started handling TestSagaEvent by TestSagaForEventHandler', $this->logger->logs[0]['message']);
            self::assertSame(['sagaId' => '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a'], $this->logger->logs[0]['context']);

            self::assertSame('[Saga] Failed handling TestSagaEvent by TestSagaForEventHandler', $this->logger->logs[1]['message']);
            self::assertSame('Saga execution failed', $this->logger->logs[1]['context']['exception']);
            self::assertSame(Exception::class, $this->logger->logs[1]['context']['exceptionClass']);
            self::assertSame('3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a', $this->logger->logs[1]['context']['sagaId']);
        }
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Saga\Retrying;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Repository\SagaConcurrencyException;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\Retrying\RetryingSagaEventExecutorDecorator;
use Gember\EventSourcing\Saga\SagaCommandDispatchFailedException;
use Gember\EventSourcing\Saga\SagaEventExecutor;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSaga;
use Gember\EventSourcing\Test\TestDoubles\Util\Log\TestLogger;
use Gember\EventSourcing\Test\TestDoubles\Util\Transaction\TestTransactional;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Stringable;
use Throwable;

/**
 * @internal
 */
final class RetryingSagaEventExecutorDecoratorTest extends TestCase
{
    private TestTransactional $transactional;
    private TestLogger $logger;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->transactional = new TestTransactional();
        $this->logger = new TestLogger();
    }

    #[Test]
    public function itShouldRetryWhenSagaChangedConcurrently(): void
    {
        $executor = $this->executorFailingTimes(4, SagaConcurrencyException::create());

        $saga = $this->decorate($executor)->execute(...$this->arguments());

        self::assertInstanceOf(TestSaga::class, $saga);
        self::assertSame(5, $executor->calls);
        self::assertSame([], $this->logger->logs);
    }

    #[Test]
    public function itShouldRethrowAndWarnAfterFiveAttempts(): void
    {
        $executor = $this->executorFailingTimes(5, SagaConcurrencyException::create());

        try {
            $this->decorate($executor)->execute(...$this->arguments());

            self::fail('Expected SagaConcurrencyException');
        } catch (SagaConcurrencyException) {
            self::assertSame(5, $executor->calls);
        }

        self::assertCount(1, $this->logger->logs);
        self::assertSame(
            '[Saga] Gave up handling stdClass by TestSaga after 5 attempt(s): saga was changed concurrently',
            $this->logger->logs[0]['message'],
        );
        self::assertSame(['saga-id', 'another-id'], $this->logger->logs[0]['context']['sagaIds']);
    }

    #[Test]
    public function itShouldNotRetryInsideTransactionItDidNotOpen(): void
    {
        $this->transactional->active = true;
        $executor = $this->executorFailingTimes(1, SagaConcurrencyException::create());

        try {
            $this->decorate($executor)->execute(...$this->arguments());

            self::fail('Expected SagaConcurrencyException');
        } catch (SagaConcurrencyException) {
            self::assertSame(1, $executor->calls);
        }

        self::assertCount(1, $this->logger->logs);
        self::assertTrue($this->logger->logs[0]['context']['insideTransaction']);
    }

    #[Test]
    public function itShouldNotRetryOtherExceptions(): void
    {
        $executor = $this->executorFailingTimes(1, new RuntimeException('Handler failed'));

        try {
            $this->decorate($executor)->execute(...$this->arguments());

            self::fail('Expected RuntimeException');
        } catch (RuntimeException) {
            self::assertSame(1, $executor->calls);
        }

        self::assertSame([], $this->logger->logs);
    }

    #[Test]
    public function itShouldNotRetryWhenDispatchingCommandsFailed(): void
    {
        $executor = $this->executorFailingTimes(
            1,
            SagaCommandDispatchFailedException::create(TestSaga::class, SagaConcurrencyException::create()),
        );

        try {
            $this->decorate($executor)->execute(...$this->arguments());

            self::fail('Expected SagaCommandDispatchFailedException');
        } catch (SagaCommandDispatchFailedException) {
            self::assertSame(1, $executor->calls);
        }
    }

    private function decorate(SagaEventExecutor $executor): RetryingSagaEventExecutorDecorator
    {
        return new RetryingSagaEventExecutorDecorator($executor, $this->transactional, $this->logger);
    }

    /**
     * @return array{object, SagaEventSubscriberDefinition, class-string, string, string, string}
     */
    private function arguments(): array
    {
        return [
            new stdClass(),
            new SagaEventSubscriberDefinition(stdClass::class, 'onEvent', CreationPolicy::IfMissing),
            TestSaga::class,
            'onEvent',
            'saga-id',
            'another-id',
        ];
    }

    private function executorFailingTimes(int $failures, Throwable $exception): FailingSagaEventExecutor
    {
        return new FailingSagaEventExecutor($failures, $exception);
    }
}

/**
 * @internal
 */
final class FailingSagaEventExecutor implements SagaEventExecutor
{
    public int $calls = 0;

    public function __construct(
        private readonly int $failures,
        private readonly Throwable $exception,
    ) {}

    #[Override]
    public function execute(
        object $event,
        SagaEventSubscriberDefinition $eventSubscriberDefinition,
        string $sagaClassName,
        string $methodName,
        string|Stringable ...$sagaIdValues,
    ): object {
        if (++$this->calls <= $this->failures) {
            throw $this->exception;
        }

        return new TestSaga();
    }
}

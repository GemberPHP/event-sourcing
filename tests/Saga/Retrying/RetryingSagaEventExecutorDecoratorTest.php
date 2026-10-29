<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Saga\Retrying;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Repository\SagaConcurrencyException;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\Retrying\RetryingSagaEventExecutorDecorator;
use Gember\EventSourcing\Saga\SagaEventExecutor;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSaga;
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
    #[Test]
    public function itShouldRetryWhenSagaChangedConcurrently(): void
    {
        $executor = $this->executorFailingTimes(2, SagaConcurrencyException::create());

        $saga = (new RetryingSagaEventExecutorDecorator($executor))->execute(...$this->arguments());

        self::assertInstanceOf(TestSaga::class, $saga);
        self::assertSame(3, $executor->calls);
    }

    #[Test]
    public function itShouldRethrowAfterThreeAttempts(): void
    {
        $executor = $this->executorFailingTimes(3, SagaConcurrencyException::create());

        try {
            (new RetryingSagaEventExecutorDecorator($executor))->execute(...$this->arguments());

            self::fail('Expected SagaConcurrencyException');
        } catch (SagaConcurrencyException) {
            self::assertSame(3, $executor->calls);
        }
    }

    #[Test]
    public function itShouldNotRetryOtherExceptions(): void
    {
        $executor = $this->executorFailingTimes(1, new RuntimeException('Handler failed'));

        try {
            (new RetryingSagaEventExecutorDecorator($executor))->execute(...$this->arguments());

            self::fail('Expected RuntimeException');
        } catch (RuntimeException) {
            self::assertSame(1, $executor->calls);
        }
    }

    /**
     * @return array{object, SagaEventSubscriberDefinition, class-string, string, string}
     */
    private function arguments(): array
    {
        return [
            new stdClass(),
            new SagaEventSubscriberDefinition(stdClass::class, 'onEvent', CreationPolicy::IfMissing),
            TestSaga::class,
            'onEvent',
            'saga-id',
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
        string|Stringable $sagaIdValue,
    ): object {
        if (++$this->calls <= $this->failures) {
            throw $this->exception;
        }

        return new TestSaga();
    }
}

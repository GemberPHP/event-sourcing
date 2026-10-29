<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Saga\Default;

use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Repository\SagaConcurrencyException;
use Gember\EventSourcing\Repository\SagaNotFoundException;
use Gember\EventSourcing\Repository\SagaStore;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\CommandRecorder;
use Gember\EventSourcing\Saga\Default\DefaultSagaEventExecutor;
use Gember\EventSourcing\Saga\SagaCommandDispatchFailedException;
use Gember\EventSourcing\Test\TestDoubles\Util\Messaging\MessageBus\TestCommandBus;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

/**
 * @internal
 */
final class DefaultSagaEventExecutorTest extends TestCase
{
    private InMemorySagaStore $sagaStore;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->sagaStore = new InMemorySagaStore();
    }

    #[Test]
    public function itShouldCreateSagaSaveItAndDispatchRecordedCommands(): void
    {
        $executor = new DefaultSagaEventExecutor($commandBus = new TestCommandBus(), $this->sagaStore);

        $saga = $executor->execute(new stdClass(), $this->definition(CreationPolicy::IfMissing), RecordingSaga::class, 'onEvent', 'saga-id');

        self::assertInstanceOf(RecordingSaga::class, $saga);
        self::assertSame(1, $saga->handled);
        self::assertSame([$saga], $this->sagaStore->saved);
        self::assertCount(1, $commandBus->commands);
    }

    #[Test]
    public function itShouldHandleExistingSagaFoundByAnyOfTheSagaIds(): void
    {
        $this->sagaStore->sagas['payment-1'] = $existing = new RecordingSaga();

        $executor = new DefaultSagaEventExecutor(new TestCommandBus(), $this->sagaStore);

        $saga = $executor->execute(new stdClass(), $this->definition(CreationPolicy::Never), RecordingSaga::class, 'onEvent', 'order-1', 'payment-1');

        self::assertSame($existing, $saga);
        self::assertSame(1, $existing->handled);
    }

    #[Test]
    public function itShouldReturnNullWhenSagaNotFoundAndCreationPolicyIsNever(): void
    {
        $executor = new DefaultSagaEventExecutor(new TestCommandBus(), $this->sagaStore);

        self::assertNull($executor->execute(new stdClass(), $this->definition(CreationPolicy::Never), RecordingSaga::class, 'onEvent', 'saga-id'));
        self::assertSame([], $this->sagaStore->saved);
    }

    #[Test]
    public function itShouldWrapSagaConcurrencyExceptionWhileDispatchingCommands(): void
    {
        $commandBus = new class implements CommandBus {
            public function handle(object $command): void
            {
                // E.g. a saga handling the dispatched command's events synchronously, running out of retries
                throw SagaConcurrencyException::create();
            }
        };

        $executor = new DefaultSagaEventExecutor($commandBus, $this->sagaStore);

        try {
            $executor->execute(new stdClass(), $this->definition(CreationPolicy::IfMissing), RecordingSaga::class, 'onEvent', 'saga-id');

            self::fail('Expected SagaCommandDispatchFailedException');
        } catch (SagaCommandDispatchFailedException $exception) {
            self::assertInstanceOf(SagaConcurrencyException::class, $exception->getPrevious());
            self::assertCount(1, $this->sagaStore->saved);
        }
    }

    private function definition(CreationPolicy $policy): SagaEventSubscriberDefinition
    {
        return new SagaEventSubscriberDefinition(stdClass::class, 'onEvent', $policy);
    }
}

/**
 * @internal
 */
final class RecordingSaga
{
    public int $handled = 0;

    public function onEvent(object $event, CommandRecorder $commandRecorder): void
    {
        ++$this->handled;
        $commandRecorder->record(new stdClass());
    }
}

/**
 * @internal
 */
final class InMemorySagaStore implements SagaStore
{
    /**
     * @var array<string, object>
     */
    public array $sagas = [];

    /**
     * @var list<object>
     */
    public array $saved = [];

    public function get(string $sagaClassName, string|Stringable ...$sagaIds): object
    {
        foreach ($sagaIds as $sagaId) {
            if (isset($this->sagas[(string) $sagaId])) {
                return $this->sagas[(string) $sagaId];
            }
        }

        throw SagaNotFoundException::create();
    }

    public function save(object $saga): void
    {
        $this->saved[] = $saga;
    }
}

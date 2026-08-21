<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Saga\Transactional;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Saga\Transactional\TransactionalSagaEventExecutorDecorator;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaEventExecutor;
use Gember\EventSourcing\Test\TestDoubles\Util\Transaction\TestTransactional;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class TransactionalSagaEventExecutorDecoratorTest extends TestCase
{
    private TestSagaEventExecutor $innerExecutor;
    private TestTransactional $transactional;
    private TransactionalSagaEventExecutorDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerExecutor = new TestSagaEventExecutor();
        $this->transactional = new TestTransactional();
        $this->decorator = new TransactionalSagaEventExecutorDecorator(
            $this->innerExecutor,
            $this->transactional,
        );
    }

    #[Test]
    public function itShouldWrapExecuteInTransaction(): void
    {
        $event = new stdClass();
        $definition = new SagaEventSubscriberDefinition(
            stdClass::class,
            'onEvent',
            CreationPolicy::IfMissing,
        );

        $this->decorator->execute($event, $definition, stdClass::class, 'onEvent', 'saga-id-1');

        self::assertTrue($this->transactional->wasCalled);
        self::assertTrue($this->innerExecutor->wasExecuted);
        self::assertSame($event, $this->innerExecutor->lastEvent);
        self::assertSame(stdClass::class, $this->innerExecutor->lastSagaClassName);
        self::assertSame('onEvent', $this->innerExecutor->lastMethodName);
        self::assertSame('saga-id-1', (string) $this->innerExecutor->lastSagaIdValue);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Saga;

use Gember\EventSourcing\Saga\CommandRecorder;
use Gember\EventSourcing\Test\TestDoubles\Util\Messaging\MessageBus\TestCommandBus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class CommandRecorderTest extends TestCase
{
    #[Test]
    public function itShouldRecordCommandsWithoutDispatching(): void
    {
        $commandBus = new TestCommandBus();
        $commandRecorder = new CommandRecorder($commandBus);

        $commandRecorder->record(new stdClass());
        $commandRecorder->record(new stdClass());

        self::assertEmpty($commandBus->commands);
    }

    #[Test]
    public function itShouldFlushRecordedCommandsInOrder(): void
    {
        $commandBus = new TestCommandBus();
        $commandRecorder = new CommandRecorder($commandBus);

        $command1 = new stdClass();
        $command1->name = 'first';
        $command2 = new stdClass();
        $command2->name = 'second';
        $command3 = new stdClass();
        $command3->name = 'third';

        $commandRecorder->record($command1);
        $commandRecorder->record($command2);
        $commandRecorder->record($command3);

        $commandRecorder->flush();

        self::assertCount(3, $commandBus->commands);
        self::assertSame($command1, $commandBus->commands[0]);
        self::assertSame($command2, $commandBus->commands[1]);
        self::assertSame($command3, $commandBus->commands[2]);
    }

    #[Test]
    public function itShouldClearRecordedCommandsAfterFlush(): void
    {
        $commandBus = new TestCommandBus();
        $commandRecorder = new CommandRecorder($commandBus);

        $commandRecorder->record(new stdClass());
        $commandRecorder->record(new stdClass());

        $commandRecorder->flush();

        self::assertCount(2, $commandBus->commands);

        $commandBus->commands = [];
        $commandRecorder->flush();

        self::assertEmpty($commandBus->commands);
    }

    #[Test]
    public function itShouldHandleFlushWithNoRecordedCommands(): void
    {
        $commandBus = new TestCommandBus();
        $commandRecorder = new CommandRecorder($commandBus);

        $commandRecorder->flush();

        self::assertEmpty($commandBus->commands);
    }
}

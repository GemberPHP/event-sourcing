<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\UseCase\Attribute;

use Gember\EventSourcing\UseCase\Attribute\Snapshot;
use Gember\EventSourcing\Util\Time\Duration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SnapshotTest extends TestCase
{
    #[Test]
    public function itShouldCreateWithAllDefaultsNull(): void
    {
        $snapshot = new Snapshot();

        self::assertNull($snapshot->afterEvents);
        self::assertNull($snapshot->afterSourcingTime);
        self::assertNull($snapshot->onEvents);
    }

    #[Test]
    public function itShouldCreateWithAfterEvents(): void
    {
        $snapshot = new Snapshot(afterEvents: 10);

        self::assertSame(10, $snapshot->afterEvents);
    }

    #[Test]
    public function itShouldCreateWithAfterSourcingTime(): void
    {
        $duration = Duration::seconds(5);

        $snapshot = new Snapshot(afterSourcingTime: $duration);

        self::assertSame($duration, $snapshot->afterSourcingTime);
    }

    #[Test]
    public function itShouldNormalizeSingleOnEventToArray(): void
    {
        $snapshot = new Snapshot(onEvent: 'SomeEvent');

        self::assertSame(['SomeEvent'], $snapshot->onEvents);
    }

    #[Test]
    public function itShouldPassThroughOnEventsArray(): void
    {
        $snapshot = new Snapshot(onEvents: ['EventA', 'EventB']);

        self::assertSame(['EventA', 'EventB'], $snapshot->onEvents);
    }

    #[Test]
    public function itShouldMergeOnEventAndOnEvents(): void
    {
        $snapshot = new Snapshot(onEvent: 'EventA', onEvents: ['EventB', 'EventC']);

        self::assertSame(['EventA', 'EventB', 'EventC'], $snapshot->onEvents);
    }

    #[Test]
    public function itShouldReturnNullWhenNeitherOnEventNorOnEventsProvided(): void
    {
        $snapshot = new Snapshot(afterEvents: 5);

        self::assertNull($snapshot->onEvents);
    }

    #[Test]
    public function itShouldRejectZeroAfterEvents(): void
    {
        self::expectException(\InvalidArgumentException::class);

        new Snapshot(afterEvents: 0);
    }

    #[Test]
    public function itShouldRejectNegativeAfterEvents(): void
    {
        self::expectException(\InvalidArgumentException::class);

        new Snapshot(afterEvents: -1);
    }
}

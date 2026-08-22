<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Time;

use Gember\EventSourcing\Util\Time\Duration;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DurationTest extends TestCase
{
    #[Test]
    public function itShouldCreateWithDefaultValue(): void
    {
        $duration = new Duration();

        self::assertSame(0, $duration->milliseconds);
    }

    #[Test]
    public function itShouldCreateWithCustomMilliseconds(): void
    {
        $duration = new Duration(500);

        self::assertSame(500, $duration->milliseconds);
    }

    #[Test]
    public function itShouldCreateFromMillisecondsFactory(): void
    {
        $duration = Duration::milliseconds(750);

        self::assertSame(750, $duration->milliseconds);
    }

    #[Test]
    public function itShouldCreateFromSecondsFactory(): void
    {
        $duration = Duration::seconds(3);

        self::assertSame(3000, $duration->milliseconds);
    }

    #[Test]
    public function itShouldRejectNegativeMilliseconds(): void
    {
        self::expectException(InvalidArgumentException::class);

        new Duration(-1);
    }
}

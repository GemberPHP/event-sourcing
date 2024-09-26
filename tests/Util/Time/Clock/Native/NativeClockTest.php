<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Time\Clock\Native;

use Gember\EventSourcing\Util\Time\Clock\Native\NativeClock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

/**
 * @internal
 */
final class NativeClockTest extends TestCase
{
    #[Test]
    public function itShouldReturnTime(): void
    {
        $clock = new NativeClock();

        self::assertEquals(
            new DateTimeImmutable('2024-05-29 10:00:00'),
            $clock->now('2024-05-29 10:00:00'),
        );
    }
}

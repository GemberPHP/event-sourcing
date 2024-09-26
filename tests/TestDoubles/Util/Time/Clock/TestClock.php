<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\Time\Clock;

use DateTimeImmutable;
use Gember\EventSourcing\Util\Time\Clock\Clock;

final class TestClock implements Clock
{
    public DateTimeImmutable $time;

    public function now(string $time = 'now'): DateTimeImmutable
    {
        return $this->time ?? new DateTimeImmutable();
    }
}

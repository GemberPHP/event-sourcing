<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Time\Clock\Native;

use DateTimeImmutable;
use Gember\EventSourcing\Util\Time\Clock\Clock;
use Override;

final readonly class NativeClock implements Clock
{
    #[Override]
    public function now(string $time = 'now'): DateTimeImmutable
    {
        return new DateTimeImmutable($time);
    }
}

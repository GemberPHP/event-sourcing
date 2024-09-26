<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Time\Clock;

use DateTimeImmutable;

interface Clock
{
    public function now(string $time = 'now'): DateTimeImmutable;
}

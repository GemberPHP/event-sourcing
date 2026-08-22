<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Time;

use InvalidArgumentException;

final readonly class Duration
{
    public function __construct(
        public int $milliseconds = 0,
    ) {
        if ($milliseconds < 0) {
            throw new InvalidArgumentException('Duration must not be negative');
        }
    }

    public static function milliseconds(int $milliseconds): self
    {
        return new self($milliseconds);
    }

    public static function seconds(int $seconds): self
    {
        return new self($seconds * 1000);
    }
}

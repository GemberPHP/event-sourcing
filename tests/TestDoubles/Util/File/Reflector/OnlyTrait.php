<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\File\Reflector;

trait OnlyTrait
{
    public function name(): string
    {
        return self::class;
    }
}

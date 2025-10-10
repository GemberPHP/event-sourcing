<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga;

interface NamedSaga
{
    public static function getName(): string;
}

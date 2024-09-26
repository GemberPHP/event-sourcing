<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Generator\Identity;

interface IdentityGenerator
{
    public function generate(): string;
}

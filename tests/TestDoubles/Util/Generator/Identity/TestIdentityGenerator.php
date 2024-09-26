<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\Generator\Identity;

use Gember\EventSourcing\Util\Generator\Identity\IdentityGenerator;

final readonly class TestIdentityGenerator implements IdentityGenerator
{
    public function generate(): string
    {
        return 'be07b19b-c7ab-429e-a9c3-6b7d942122c0';
    }
}

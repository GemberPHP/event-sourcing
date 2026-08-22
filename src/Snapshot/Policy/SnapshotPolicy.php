<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Snapshot\Policy;

use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;

interface SnapshotPolicy
{
    public function shouldSnapshot(SnapshotDefinition $definition, SnapshotContext $context): bool;
}

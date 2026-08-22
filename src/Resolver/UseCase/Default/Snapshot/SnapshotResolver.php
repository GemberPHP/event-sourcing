<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\Default\Snapshot;

use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;

interface SnapshotResolver
{
    /**
     * @param class-string $useCaseClassName
     */
    public function resolve(string $useCaseClassName): ?SnapshotDefinition;
}

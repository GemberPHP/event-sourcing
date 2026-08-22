<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Snapshot\Policy;

use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;
use Override;

final readonly class AfterSourcingTimeSnapshotPolicy implements SnapshotPolicy
{
    #[Override]
    public function shouldSnapshot(SnapshotDefinition $definition, SnapshotContext $context): bool
    {
        if ($definition->afterSourcingTimeMs === null) {
            return false;
        }

        return $context->sourcingDurationMs >= $definition->afterSourcingTimeMs;
    }
}

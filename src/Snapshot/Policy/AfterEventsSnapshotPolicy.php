<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Snapshot\Policy;

use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;
use Override;

final readonly class AfterEventsSnapshotPolicy implements SnapshotPolicy
{
    #[Override]
    public function shouldSnapshot(SnapshotDefinition $definition, SnapshotContext $context): bool
    {
        if ($definition->afterEvents === null) {
            return false;
        }

        $eventsSinceLastSnapshot = $context->eventCount - $context->eventCountAtLastSnapshot;

        return $eventsSinceLastSnapshot >= $definition->afterEvents;
    }
}

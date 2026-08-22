<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Snapshot\Policy;

use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;
use Override;

final readonly class OnEventsSnapshotPolicy implements SnapshotPolicy
{
    #[Override]
    public function shouldSnapshot(SnapshotDefinition $definition, SnapshotContext $context): bool
    {
        if ($definition->onEvents === null) {
            return false;
        }

        foreach ($context->appliedEvents as $appliedEvent) {
            if (in_array($appliedEvent::class, $definition->onEvents, true)) {
                return true;
            }
        }

        return false;
    }
}

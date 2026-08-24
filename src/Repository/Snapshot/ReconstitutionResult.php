<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository\Snapshot;

use Gember\EventSourcing\UseCase\EventSourcedUseCase;

/**
 * @internal
 */
final readonly class ReconstitutionResult
{
    public function __construct(
        public EventSourcedUseCase $useCase,
        public int $eventCount,
        public int $eventCountAtLastSnapshot,
    ) {}
}

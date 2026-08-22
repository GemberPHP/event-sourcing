<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\Default\Snapshot\Attribute;

use Gember\EventSourcing\Resolver\UseCase\Default\Snapshot\SnapshotResolver;
use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;
use Gember\EventSourcing\UseCase\Attribute\Snapshot;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;

final readonly class AttributeSnapshotResolver implements SnapshotResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $useCaseClassName): ?SnapshotDefinition
    {
        $attributes = $this->attributeResolver->getAttributesForClass(
            $useCaseClassName,
            Snapshot::class,
        );

        if ($attributes === []) {
            return null;
        }

        $snapshot = $attributes[0];

        return new SnapshotDefinition(
            $snapshot->afterEvents,
            $snapshot->afterSourcingTime?->milliseconds,
            $snapshot->onEvents,
        );
    }
}

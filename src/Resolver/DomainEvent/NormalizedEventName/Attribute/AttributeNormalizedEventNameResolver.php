<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Attribute;

use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\NormalizedEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\UnresolvableEventNameException;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;

final readonly class AttributeNormalizedEventNameResolver implements NormalizedEventNameResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $eventClassName): string
    {
        $attributes = $this->attributeResolver->getAttributesForClass($eventClassName, DomainEvent::class);

        if ($attributes === []) {
            throw UnresolvableEventNameException::create(
                $eventClassName,
                'Event does not contain #[DomainEvent] attribute',
            );
        }

        return $attributes[array_key_first($attributes)]->name;
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Attribute;

use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\EventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\UnresolvableEventNameException;
use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;

/**
 * Resolve event name by attribute #[DomainEvent].
 */
final readonly class AttributeEventNameResolver implements EventNameResolver
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

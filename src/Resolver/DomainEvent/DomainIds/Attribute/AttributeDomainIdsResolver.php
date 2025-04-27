<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\DomainIds\Attribute;

use Gember\EventSourcing\DomainContext\Attribute\DomainId;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\DomainIdsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\UnresolvableDomainIdsException;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use Stringable;

final readonly class AttributeDomainIdsResolver implements DomainIdsResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(object $event): array
    {
        $properties = $this->attributeResolver->getPropertyNamesWithAttribute($event::class, DomainId::class);

        if ($properties === []) {
            throw UnresolvableDomainIdsException::create(
                $event::class,
                'Event does not contain #[DomainId] attribute',
            );
        }

        /** @var list<string|Stringable> */
        return array_map(fn($property) => $event->{$property}, $properties);
    }
}

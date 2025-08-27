<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\DomainTags\Attribute;

use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\DomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\UnresolvableDomainTagsException;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use Stringable;

final readonly class AttributeDomainTagsResolver implements DomainTagsResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(object $event): array
    {
        $properties = $this->attributeResolver->getPropertyNamesWithAttribute($event::class, DomainTag::class);

        if ($properties === []) {
            throw UnresolvableDomainTagsException::create(
                $event::class,
                'Event does not contain #[DomainTag] attribute',
            );
        }

        /** @var list<string|Stringable> */
        return array_map(fn($property) => $event->{$property}, $properties);
    }
}

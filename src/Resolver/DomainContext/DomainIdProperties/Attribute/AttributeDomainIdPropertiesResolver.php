<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties\Attribute;

use Gember\EventSourcing\DomainContext\Attribute\DomainId;
use Gember\EventSourcing\DomainContext\EventSourcedDomainContext;
use Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties\DomainIdPropertiesResolver;
use Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties\UnresolvableDomainIdPropertiesException;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;

/**
 * @template T of EventSourcedDomainContext
 *
 * @implements DomainIdPropertiesResolver<T>
 */
final readonly class AttributeDomainIdPropertiesResolver implements DomainIdPropertiesResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $domainContextClassName): array
    {
        $properties = $this->attributeResolver->getPropertyNamesWithAttribute($domainContextClassName, DomainId::class);

        if ($properties === []) {
            throw UnresolvableDomainIdPropertiesException::create(
                $domainContextClassName,
                'Context does not contain properties with #[DomainId] attribute',
            );
        }

        return $properties;
    }
}

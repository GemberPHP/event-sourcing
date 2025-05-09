<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\DomainIdProperties\Attribute;

use Gember\EventSourcing\UseCase\Attribute\DomainId;
use Gember\EventSourcing\Resolver\UseCase\DomainIdProperties\DomainIdPropertiesResolver;
use Gember\EventSourcing\Resolver\UseCase\DomainIdProperties\UnresolvableDomainIdPropertiesException;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;

final readonly class AttributeDomainIdPropertiesResolver implements DomainIdPropertiesResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $useCaseClassName): array
    {
        $properties = $this->attributeResolver->getPropertyNamesWithAttribute($useCaseClassName, DomainId::class);

        if ($properties === []) {
            throw UnresolvableDomainIdPropertiesException::create(
                $useCaseClassName,
                'Use case does not contain properties with #[DomainId] attribute',
            );
        }

        return $properties;
    }
}

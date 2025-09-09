<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\DomainTagProperties\Attribute;

use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\Resolver\UseCase\DomainTagProperties\DomainTagsPropertiesResolver;
use Gember\EventSourcing\Resolver\UseCase\DomainTagProperties\UnresolvableDomainTagPropertiesException;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use ReflectionProperty;

final readonly class AttributeDomainTagsPropertiesResolver implements DomainTagsPropertiesResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $useCaseClassName): array
    {
        $properties = $this->attributeResolver->getPropertiesWithAttribute($useCaseClassName, DomainTag::class);

        if ($properties === []) {
            throw UnresolvableDomainTagPropertiesException::create(
                $useCaseClassName,
                'Use case does not contain properties with #[DomainTag] attribute',
            );
        }

        $names = [];

        /** @var ReflectionProperty $reflectionProperty */
        foreach ($properties as [$reflectionProperty]) {
            $names[] = $reflectionProperty->getName();
        }

        return $names;
    }
}

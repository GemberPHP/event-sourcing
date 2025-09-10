<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Common\DomainTag\Attribute;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\Common\DomainTag\UnresolvableDomainTagException;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use ReflectionProperty;

final readonly class AttributeDomainTagResolver implements DomainTagResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $className): array
    {
        $properties = $this->attributeResolver->getPropertiesWithAttribute($className, DomainTag::class);

        $domainTags = [];

        if ($properties === []) {
            throw UnresolvableDomainTagException::create($className, 'No #[DomainTag] attributes were found.');
        }

        /** @var ReflectionProperty $reflectionProperty */
        foreach ($properties as [$reflectionProperty]) {
            $domainTags[] = new DomainTagDefinition(
                $reflectionProperty->getName(),
                DomainTagType::Property,
            );
        }

        return $domainTags;
    }
}

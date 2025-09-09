<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainMessage\DomainTags\Attribute;

use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\Resolver\DomainMessage\DomainTags\DomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainMessage\DomainTags\UnresolvableDomainTagsException;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use ReflectionProperty;
use Stringable;

final readonly class AttributeDomainTagsResolver implements DomainTagsResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(object $message): array
    {
        $properties = $this->attributeResolver->getPropertiesWithAttribute($message::class, DomainTag::class);

        if ($properties === []) {
            throw UnresolvableDomainTagsException::create(
                $message::class,
                'Domain message (event/command) does not contain #[DomainTag] attribute',
            );
        }

        $domainTags = [];

        /** @var ReflectionProperty $reflectionProperty */
        foreach ($properties as [$reflectionProperty]) {
            /** @var string|Stringable $domainTag */
            $domainTag = $message->{$reflectionProperty->getName()};
            $domainTags[] = $domainTag;
        }

        return $domainTags;
    }
}

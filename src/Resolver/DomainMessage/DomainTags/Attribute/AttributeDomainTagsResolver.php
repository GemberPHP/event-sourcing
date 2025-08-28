<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainMessage\DomainTags\Attribute;

use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\Resolver\DomainMessage\DomainTags\DomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainMessage\DomainTags\UnresolvableDomainTagsException;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use Stringable;

final readonly class AttributeDomainTagsResolver implements DomainTagsResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(object $message): array
    {
        $properties = $this->attributeResolver->getPropertyNamesWithAttribute($message::class, DomainTag::class);

        if ($properties === []) {
            throw UnresolvableDomainTagsException::create(
                $message::class,
                'Domain message (event/command) does not contain #[DomainTag] attribute',
            );
        }

        /** @var list<string|Stringable> */
        return array_map(fn($property) => $message->{$property}, $properties);
    }
}

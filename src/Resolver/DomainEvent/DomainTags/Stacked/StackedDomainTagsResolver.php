<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\DomainTags\Stacked;

use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\DomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\UnresolvableDomainTagsCollectionException;
use Gember\EventSourcing\Resolver\DomainEvent\DomainTags\UnresolvableDomainTagsException;
use Override;

final readonly class StackedDomainTagsResolver implements DomainTagsResolver
{
    /**
     * @param iterable<DomainTagsResolver> $domainTagsResolvers
     */
    public function __construct(
        private iterable $domainTagsResolvers,
    ) {}

    #[Override]
    public function resolve(object $event): array
    {
        $exceptions = [];

        foreach ($this->domainTagsResolvers as $domainTagsResolver) {
            try {
                return $domainTagsResolver->resolve($event);
            } catch (UnresolvableDomainTagsException $exception) {
                $exceptions[] = $exception;

                continue;
            }
        }

        throw UnresolvableDomainTagsCollectionException::withExceptions(
            $event::class,
            'None DomainTagsResolver could resolve domainTags',
            ...$exceptions,
        );
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainMessage\DomainTags\Stacked;

use Gember\EventSourcing\Resolver\DomainMessage\DomainTags\DomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainMessage\DomainTags\UnresolvableDomainTagsCollectionException;
use Gember\EventSourcing\Resolver\DomainMessage\DomainTags\UnresolvableDomainTagsException;
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
    public function resolve(object $message): array
    {
        $exceptions = [];

        foreach ($this->domainTagsResolvers as $domainTagsResolver) {
            try {
                return $domainTagsResolver->resolve($message);
            } catch (UnresolvableDomainTagsException $exception) {
                $exceptions[] = $exception;

                continue;
            }
        }

        throw UnresolvableDomainTagsCollectionException::withExceptions(
            $message::class,
            'None DomainTagsResolver could resolve domainTags',
            ...$exceptions,
        );
    }
}

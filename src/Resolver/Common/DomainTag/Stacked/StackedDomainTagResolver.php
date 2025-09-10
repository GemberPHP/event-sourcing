<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Common\DomainTag\Stacked;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\UnresolvableDomainTagException;
use Override;

final readonly class StackedDomainTagResolver implements DomainTagResolver
{
    /**
     * @param iterable<DomainTagResolver> $domainTagResolvers
     */
    public function __construct(
        private iterable $domainTagResolvers,
    ) {}

    #[Override]
    public function resolve(string $className): array
    {
        foreach ($this->domainTagResolvers as $domainTagResolver) {
            try {
                return $domainTagResolver->resolve($className);
            } catch (UnresolvableDomainTagException) {
                continue;
            }
        }

        return [];
    }
}

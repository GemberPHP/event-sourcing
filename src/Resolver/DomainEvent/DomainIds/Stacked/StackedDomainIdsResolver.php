<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\DomainIds\Stacked;

use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\DomainIdsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\UnresolvableDomainIdsCollectionException;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\UnresolvableDomainIdsException;
use Override;

final readonly class StackedDomainIdsResolver implements DomainIdsResolver
{
    /**
     * @param iterable<DomainIdsResolver> $eventDomainIdsResolvers
     */
    public function __construct(
        private iterable $eventDomainIdsResolvers,
    ) {}

    #[Override]
    public function resolve(object $event): array
    {
        $exceptions = [];

        foreach ($this->eventDomainIdsResolvers as $eventDomainIdsResolver) {
            try {
                return $eventDomainIdsResolver->resolve($event);
            } catch (UnresolvableDomainIdsException $exception) {
                $exceptions[] = $exception;

                continue;
            }
        }

        throw UnresolvableDomainIdsCollectionException::withExceptions(
            $event::class,
            'None DomainIdsResolver could resolve domainIds',
            ...$exceptions,
        );
    }
}

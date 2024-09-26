<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Stacked;

use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\NormalizedEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\UnresolvableEventNameCollectionException;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\UnresolvableEventNameException;
use Override;

final readonly class StackedNormalizedEventNameResolver implements NormalizedEventNameResolver
{
    /**
     * @param iterable<NormalizedEventNameResolver> $eventNameResolvers
     */
    public function __construct(
        private iterable $eventNameResolvers,
    ) {}

    #[Override]
    public function resolve(string $eventClassName): string
    {
        $exceptions = [];

        foreach ($this->eventNameResolvers as $eventNameResolver) {
            try {
                return $eventNameResolver->resolve($eventClassName);
            } catch (UnresolvableEventNameException $exception) {
                $exceptions[] = $exception;

                continue;
            }
        }

        throw UnresolvableEventNameCollectionException::withExceptions(
            $eventClassName,
            'None NormalizedEventNameResolver could resolve event name',
            ...$exceptions,
        );
    }
}

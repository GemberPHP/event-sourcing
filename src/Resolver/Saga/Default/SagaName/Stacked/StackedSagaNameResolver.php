<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga\Default\SagaName\Stacked;

use Gember\EventSourcing\Resolver\Saga\Default\SagaName\SagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\UnresolvableSagaNameException;
use Override;

final readonly class StackedSagaNameResolver implements SagaNameResolver
{
    /**
     * @param iterable<SagaNameResolver> $sagaNameResolvers
     */
    public function __construct(
        private iterable $sagaNameResolvers,
        private SagaNameResolver $fallbackSagaNameResolver,
    ) {}

    #[Override]
    public function resolve(string $sagaClassName): string
    {
        foreach ($this->sagaNameResolvers as $sagaNameResolver) {
            try {
                return $sagaNameResolver->resolve($sagaClassName);
            } catch (UnresolvableSagaNameException) {
                continue;
            }
        }

        return $this->fallbackSagaNameResolver->resolve($sagaClassName);
    }
}

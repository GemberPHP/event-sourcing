<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga\Default;

use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdResolver;
use Gember\EventSourcing\Resolver\Saga\Default\EventSubscriber\SagaEventSubscriberResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\SagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\SagaDefinition;
use Gember\EventSourcing\Resolver\Saga\SagaResolver;
use Gember\EventSourcing\Resolver\Saga\UnresolvableSagaException;
use Override;

final readonly class DefaultSagaResolver implements SagaResolver
{
    public function __construct(
        private SagaNameResolver $sagaNameResolver,
        private SagaIdResolver $sagaIdResolver,
        private SagaEventSubscriberResolver $sagaEventSubscriberResolver,
    ) {}

    #[Override]
    public function resolve(string $sagaClassName): SagaDefinition
    {
        $sagaIdDefinitions = $this->sagaIdResolver->resolve($sagaClassName);

        if ($sagaIdDefinitions === []) {
            throw UnresolvableSagaException::missingSagaId($sagaClassName);
        }

        return new SagaDefinition(
            $sagaClassName,
            $this->sagaNameResolver->resolve($sagaClassName),
            $sagaIdDefinitions,
            $this->sagaEventSubscriberResolver->resolve($sagaClassName),
        );
    }
}

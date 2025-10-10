<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\Default;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagResolver;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\EventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventDefinition;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventResolver;
use Override;

final readonly class DefaultDomainEventResolver implements DomainEventResolver
{
    public function __construct(
        private EventNameResolver $eventNameResolver,
        private DomainTagResolver $domainTagResolver,
        private SagaIdResolver $sagaIdResolver,
    ) {}

    #[Override]
    public function resolve(string $eventClassName): DomainEventDefinition
    {
        return new DomainEventDefinition(
            $eventClassName,
            $this->eventNameResolver->resolve($eventClassName),
            $this->domainTagResolver->resolve($eventClassName),
            $this->sagaIdResolver->resolve($eventClassName),
        );
    }
}

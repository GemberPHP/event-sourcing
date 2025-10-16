<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga;

use Gember\EventSourcing\Registry\Saga\SagaRegistry;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdDefinition;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdValueHelper;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventResolver;
use Gember\EventSourcing\Resolver\Saga\SagaDefinition;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Generator;

final readonly class SagaEventHandler
{
    public function __construct(
        private DomainEventResolver $domainEventResolver,
        private SagaRegistry $sagaRegistry,
        private SagaEventExecutor $sagaEventExecutor,
    ) {}

    public function __invoke(object $event): void
    {
        foreach ($this->getSagaDefinitionsForEvent($event) as [$sagaDefinition, $sagaIdDefinition, $eventSubscriberDefinition]) {
            $sagaId = SagaIdValueHelper::getSagaIdValue($event, $sagaIdDefinition);

            if ($sagaId === null) {
                continue;
            }

            $this->sagaEventExecutor->execute(
                $event,
                $eventSubscriberDefinition,
                $sagaDefinition->sagaClassName,
                $eventSubscriberDefinition->methodName,
                $sagaId,
            );
        }
    }

    /**
     * @return Generator<array{SagaDefinition, SagaIdDefinition, SagaEventSubscriberDefinition}>
     */
    private function getSagaDefinitionsForEvent(object $event): Generator
    {
        // Get all Saga ids in event
        foreach ($this->domainEventResolver->resolve($event::class)->sagaIds as $sagaIdDefinition) {
            // Get all Sagas linked to SagaIds (by saga id name)
            foreach ($this->sagaRegistry->retrieve($sagaIdDefinition->sagaIdName) as $sagaDefinition) {
                // Get subscribers for given event from these Sagas
                foreach ($sagaDefinition->eventSubscribers as $eventSubscriberDefinition) {
                    if ($eventSubscriberDefinition->eventClassName !== $event::class) {
                        continue;
                    }

                    yield [$sagaDefinition, $sagaIdDefinition, $eventSubscriberDefinition];
                }
            }
        }
    }
}

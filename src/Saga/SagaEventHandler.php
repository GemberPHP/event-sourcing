<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Registry\Saga\SagaRegistry;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdDefinition;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdValueHelper;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventResolver;
use Gember\EventSourcing\Resolver\Saga\SagaDefinition;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Generator;
use Stringable;

final readonly class SagaEventHandler
{
    public function __construct(
        private DomainEventResolver $domainEventResolver,
        private SagaRegistry $sagaRegistry,
        private SagaEventExecutor $sagaEventExecutor,
    ) {}

    public function __invoke(object $event): void
    {
        foreach ($this->groupSagaIdsBySubscriber($event) as [$sagaDefinition, $eventSubscriberDefinition, $sagaIds]) {
            $definition = $eventSubscriberDefinition;

            /*
             * The event's saga ids are handled together: the saga that owns any of them handles the event once,
             * however many of its ids the event carries. A saga id belongs to at most one saga, so remaining ids can
             * only belong to other existing sagas, which handle the event too (but are never created for it).
             */
            while ($sagaIds !== []) {
                $saga = $this->sagaEventExecutor->execute(
                    $event,
                    $definition,
                    $sagaDefinition->sagaClassName,
                    $definition->methodName,
                    ...array_values($sagaIds),
                );

                if ($saga === null) {
                    break;
                }

                $remaining = $sagaIds;

                foreach ($sagaDefinition->sagaIds as $sagaIdDefinition) {
                    $handledSagaId = SagaIdValueHelper::getSagaIdValue($saga, $sagaIdDefinition);

                    if ($handledSagaId !== null) {
                        unset($remaining[(string) $handledSagaId]);
                    }
                }

                // The saga doesn't own any of the event's saga ids (anymore): nothing to attribute the rest to
                if (count($remaining) === count($sagaIds)) {
                    break;
                }

                $sagaIds = $remaining;
                $definition = new SagaEventSubscriberDefinition(
                    $eventSubscriberDefinition->eventClassName,
                    $eventSubscriberDefinition->methodName,
                    CreationPolicy::Never,
                );
            }
        }
    }

    /**
     * @return list<array{SagaDefinition, SagaEventSubscriberDefinition, array<array-key, string|Stringable>}>
     */
    private function groupSagaIdsBySubscriber(object $event): array
    {
        $groups = [];

        foreach ($this->getSagaDefinitionsForEvent($event) as [$sagaDefinition, $sagaIdDefinition, $eventSubscriberDefinition]) {
            $sagaId = SagaIdValueHelper::getSagaIdValue($event, $sagaIdDefinition);

            if ($sagaId === null) {
                continue;
            }

            $key = $sagaDefinition->sagaClassName . '::' . $eventSubscriberDefinition->methodName;

            $groups[$key] ??= [$sagaDefinition, $eventSubscriberDefinition, []];
            $groups[$key][2][(string) $sagaId] = $sagaId;
        }

        return array_values($groups);
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

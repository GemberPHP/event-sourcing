<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Saga;

use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Registry\Saga\SagaRegistry;
use Gember\EventSourcing\Repository\SagaNotFoundException;
use Gember\EventSourcing\Repository\SagaStore;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdValueHelper;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventResolver;
use Generator;

final readonly class SagaEventHandler
{
    public function __construct(
        private DomainEventResolver $domainEventResolver,
        private SagaRegistry $sagaRegistry,
        private SagaStore $sagaStore,
        private CommandBus $commandBus,
    ) {}

    /**
     * @throws SagaNotFoundException
     */
    public function __invoke(object $event): void
    {
        foreach ($this->getSagasForEvent($event) as [$saga, $methodName]) {
            // Run saga
            $saga->{$methodName}($event, $this->commandBus);

            // Save saga in repository
            $this->sagaStore->save($saga);
        }
    }

    /**
     * @return Generator<array{object, string}>
     */
    private function getSagasForEvent(object $event): Generator
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

                    // Get persisted Saga by saga id from repository
                    try {
                        $saga = $this->sagaStore->get(
                            $sagaDefinition->sagaClassName,
                            SagaIdValueHelper::getSagaIdValue($event, $sagaIdDefinition),
                        );
                    } catch (SagaNotFoundException) {
                        if ($eventSubscriberDefinition->policy !== CreationPolicy::IfMissing) {
                            continue;
                        }

                        $sagaClassName = $sagaDefinition->sagaClassName;
                        $saga = new $sagaClassName();
                    }

                    yield [$saga, $eventSubscriberDefinition->methodName];
                }
            }
        }
    }
}

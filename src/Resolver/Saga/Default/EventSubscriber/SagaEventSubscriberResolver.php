<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga\Default\EventSubscriber;

use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;

interface SagaEventSubscriberResolver
{
    /**
     * @param class-string $sagaClassName
     *
     * @return list<SagaEventSubscriberDefinition>
     */
    public function resolve(string $sagaClassName): array;
}

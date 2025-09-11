<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\Default\EventSubscriber;

use Gember\EventSourcing\Resolver\UseCase\EventSubscriberDefinition;

interface EventSubscriberResolver
{
    /**
     * @param class-string $useCaseClassName
     *
     * @return list<EventSubscriberDefinition>
     */
    public function resolve(string $useCaseClassName): array;
}

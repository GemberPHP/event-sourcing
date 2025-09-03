<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\Messaging\MessageBus;

use Gember\DependencyContracts\Util\Messaging\MessageBus\EventBus;

final class TestEventBus implements EventBus
{
    /**
     * @var list<object>
     */
    public array $events = [];

    public function handle(object $event): void
    {
        $this->events[] = $event;
    }
}

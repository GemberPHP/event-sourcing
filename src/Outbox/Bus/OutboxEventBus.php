<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Outbox\Bus;

use Gember\DependencyContracts\Util\Messaging\MessageBus\EventBus;
use Gember\EventSourcing\Outbox\OutboxMessageType;
use Gember\EventSourcing\Outbox\OutboxStore;
use Override;

final readonly class OutboxEventBus implements EventBus
{
    public function __construct(
        private OutboxStore $outboxStore,
    ) {}

    #[Override]
    public function handle(object $event): void
    {
        $this->outboxStore->append(OutboxMessageType::Event, $event);
    }
}

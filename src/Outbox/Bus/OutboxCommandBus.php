<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Outbox\Bus;

use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;
use Gember\EventSourcing\Outbox\OutboxMessageType;
use Gember\EventSourcing\Outbox\OutboxStore;
use Override;

final readonly class OutboxCommandBus implements CommandBus
{
    public function __construct(
        private OutboxStore $outboxStore,
    ) {}

    #[Override]
    public function handle(object $command): void
    {
        $this->outboxStore->append(OutboxMessageType::Command, $command);
    }
}

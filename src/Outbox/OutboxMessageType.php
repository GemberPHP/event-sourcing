<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Outbox;

enum OutboxMessageType: string
{
    case Event = 'event';
    case Command = 'command';
}

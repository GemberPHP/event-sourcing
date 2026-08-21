<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Outbox\Processor\Default;

use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;
use Gember\DependencyContracts\Util\Messaging\MessageBus\EventBus;
use Gember\EventSourcing\Outbox\OutboxMessageType;
use Gember\EventSourcing\Outbox\OutboxStore;
use Gember\EventSourcing\Outbox\Processor\OutboxProcessor;
use Gember\EventSourcing\Util\String\ClassNameSegmentHelper;
use Override;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class DefaultOutboxProcessor implements OutboxProcessor
{
    public function __construct(
        private OutboxStore $outboxStore,
        private EventBus $eventBus,
        private CommandBus $commandBus,
        private LoggerInterface $logger,
        private int $maxRetries = 5,
    ) {}

    #[Override]
    public function process(int $limit = 100): int
    {
        $messages = $this->outboxStore->getUnprocessedMessages($limit);
        $processed = 0;

        foreach ($messages as $message) {
            try {
                match ($message->type) {
                    OutboxMessageType::Event => $this->eventBus->handle($message->message),
                    OutboxMessageType::Command => $this->commandBus->handle($message->message),
                };

                $this->outboxStore->markAsProcessed($message->id);
                ++$processed;
            } catch (Throwable $exception) {
                $this->logger->warning(sprintf(
                    '[Outbox] Failed dispatching %s %s',
                    $message->type->value,
                    ClassNameSegmentHelper::getLastSegment($message->message::class),
                ), [
                    'messageId' => $message->id,
                    'messageType' => $message->type->value,
                    'messageName' => $message->message::class,
                    'exception' => $exception->getMessage(),
                    'exceptionClass' => $exception::class,
                ]);

                $this->outboxStore->markAsFailed($this->maxRetries, $message->id);
            }
        }

        return $processed;
    }
}

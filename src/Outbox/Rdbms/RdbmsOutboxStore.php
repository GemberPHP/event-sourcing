<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Outbox\Rdbms;

use Gember\DependencyContracts\Outbox\Rdbms\RdbmsOutboxMessage;
use Gember\DependencyContracts\Outbox\Rdbms\RdbmsOutboxRepository;
use Gember\DependencyContracts\Util\Generator\Identity\IdentityGenerator;
use Gember\DependencyContracts\Util\Serialization\Serializer\Serializer;
use Gember\EventSourcing\Outbox\OutboxMessage;
use Gember\EventSourcing\Outbox\OutboxMessageType;
use Gember\EventSourcing\Outbox\OutboxStore;
use Gember\EventSourcing\Outbox\OutboxStoreFailedException;
use Gember\EventSourcing\Util\Time\Clock\Clock;
use Override;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class RdbmsOutboxStore implements OutboxStore
{
    public function __construct(
        private RdbmsOutboxRepository $repository,
        private Serializer $serializer,
        private IdentityGenerator $identityGenerator,
        private Clock $clock,
        private ?LoggerInterface $logger = null,
    ) {}

    #[Override]
    public function append(OutboxMessageType $type, object $message): void
    {
        try {
            $this->repository->save(new RdbmsOutboxMessage(
                $this->identityGenerator->generate(),
                $type->value,
                $message::class,
                $this->serializer->serialize($message),
                $this->clock->now(),
            ));
        } catch (Throwable $exception) {
            throw OutboxStoreFailedException::withException($exception);
        }
    }

    /**
     * A message that can't be deserialized (e.g. its class was renamed or removed) can't succeed on a retry: it's
     * dead-lettered right away, so it doesn't keep the other messages of its batch from being dispatched.
     */
    #[Override]
    public function getUnprocessedMessages(int $limit): array
    {
        try {
            $rdbmsMessages = $this->repository->getUnprocessedMessages($limit);
        } catch (Throwable $exception) {
            throw OutboxStoreFailedException::withException($exception);
        }

        $messages = [];

        foreach ($rdbmsMessages as $rdbmsMessage) {
            try {
                /** @var class-string $messageName */
                $messageName = $rdbmsMessage->messageName;

                $messages[] = new OutboxMessage(
                    $rdbmsMessage->id,
                    OutboxMessageType::from($rdbmsMessage->messageType),
                    $this->serializer->deserialize($rdbmsMessage->payload, $messageName),
                );
            } catch (Throwable $exception) {
                $this->deadLetter($rdbmsMessage, $exception);
            }
        }

        return $messages;
    }

    #[Override]
    public function markAsProcessed(string ...$messageIds): void
    {
        try {
            $this->repository->markAsProcessed(...$messageIds);
        } catch (Throwable $exception) {
            throw OutboxStoreFailedException::withException($exception);
        }
    }

    #[Override]
    public function markAsFailed(int $maxRetries, string ...$messageIds): void
    {
        try {
            $this->repository->incrementRetryCount($maxRetries, ...$messageIds);
        } catch (Throwable $exception) {
            throw OutboxStoreFailedException::withException($exception);
        }
    }

    private function deadLetter(RdbmsOutboxMessage $rdbmsMessage, Throwable $exception): void
    {
        $this->logger?->error(sprintf(
            '[Outbox] Dead-lettered %s %s: it can\'t be deserialized',
            $rdbmsMessage->messageType,
            $rdbmsMessage->messageName,
        ), [
            'messageId' => $rdbmsMessage->id,
            'messageType' => $rdbmsMessage->messageType,
            'messageName' => $rdbmsMessage->messageName,
            'exception' => $exception->getMessage(),
            'exceptionClass' => $exception::class,
        ]);

        try {
            $this->repository->incrementRetryCount(0, $rdbmsMessage->id);
        } catch (Throwable $exception) {
            throw OutboxStoreFailedException::withException($exception);
        }
    }
}

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
use Throwable;

final readonly class RdbmsOutboxStore implements OutboxStore
{
    public function __construct(
        private RdbmsOutboxRepository $repository,
        private Serializer $serializer,
        private IdentityGenerator $identityGenerator,
        private Clock $clock,
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

    #[Override]
    public function getUnprocessedMessages(int $limit): array
    {
        try {
            return array_map(
                function (RdbmsOutboxMessage $rdbmsMessage) {
                    /** @var class-string $messageName */
                    $messageName = $rdbmsMessage->messageName;

                    return new OutboxMessage(
                        $rdbmsMessage->id,
                        OutboxMessageType::from($rdbmsMessage->messageType),
                        $this->serializer->deserialize($rdbmsMessage->payload, $messageName),
                    );
                },
                $this->repository->getUnprocessedMessages($limit),
            );
        } catch (Throwable $exception) {
            throw OutboxStoreFailedException::withException($exception);
        }
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
}

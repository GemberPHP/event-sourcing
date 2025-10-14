<?php

declare(strict_types=1);

namespace Gember\EventSourcing\EventStore\Loggable;

use Gember\EventSourcing\EventStore\EventStore;
use Gember\EventSourcing\EventStore\StreamQuery;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\Util\String\ClassNameSegmentHelper;
use Override;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class LoggableEventStoreDecorator implements EventStore
{
    public function __construct(
        private EventStore $eventStore,
        private LoggerInterface $logger,
    ) {}

    #[Override]
    public function load(StreamQuery $streamQuery): array
    {
        $startTime = microtime(true);

        $this->logger->info('[EventStore] Started loading events', [
            'domainTags' => array_map(fn($domainTag) => (string) $domainTag, $streamQuery->domainTags),
        ]);

        try {
            $envelopes = $this->eventStore->load($streamQuery);
        } catch (Throwable $exception) {
            $this->logger->info('[EventStore] Failed loading events', [
                'exception' => $exception->getMessage(),
                'exceptionClass' => $exception::class,
                'domainTags' => array_map(fn($domainTag) => (string) $domainTag, $streamQuery->domainTags),
                'duration' => microtime(true) - $startTime,
            ]);

            throw $exception;
        }

        $endTime = microtime(true) - $startTime;

        foreach ($envelopes as $envelope) {
            $this->logger->info(
                sprintf('[EventStore] Loaded event %s', ClassNameSegmentHelper::getLastSegment($envelope->event::class)),
                [
                    'eventId' => $envelope->eventId,
                    'appliedAt' => $envelope->appliedAt->format(DateTimeInterface::ATOM),
                    'metadata' => $envelope->metadata->metadata,
                    'domainTags' => $envelope->domainTags,
                ],
            );
        }

        $this->logger->info('[EventStore] Ended loading events', [
            'domainTags' => array_map(fn($domainTag) => (string) $domainTag, $streamQuery->domainTags),
            'duration' => $endTime,
        ]);

        return $envelopes;
    }

    #[Override]
    public function append(StreamQuery $streamQuery, ?string $lastEventId, DomainEventEnvelope ...$eventEnvelopes): void
    {
        $startTime = microtime(true);

        $this->logger->info('[EventStore] Started appending events', [
            'domainTags' => array_map(fn($domainTag) => (string) $domainTag, $streamQuery->domainTags),
        ]);

        try {
            $this->eventStore->append($streamQuery, $lastEventId, ...$eventEnvelopes);
        } catch (Throwable $exception) {
            $this->logger->info('[EventStore] Failed appending events', [
                'exception' => $exception->getMessage(),
                'domainTags' => array_map(fn($domainTag) => (string) $domainTag, $streamQuery->domainTags),
                'duration' => microtime(true) - $startTime,
            ]);

            throw $exception;
        }

        $endTime = microtime(true) - $startTime;

        foreach ($eventEnvelopes as $envelope) {
            $this->logger->info(
                sprintf('[EventStore] Appended event %s', ClassNameSegmentHelper::getLastSegment($envelope->event::class)),
                [
                    'eventId' => $envelope->eventId,
                    'appliedAt' => $envelope->appliedAt->format(DateTimeInterface::ATOM),
                    'metadata' => $envelope->metadata->metadata,
                    'domainTags' => $envelope->domainTags,
                ],
            );
        }

        $this->logger->info('[EventStore] Ended appending events', [
            'domainTags' => array_map(fn($domainTag) => (string) $domainTag, $streamQuery->domainTags),
            'duration' => $endTime,
        ]);
    }
}

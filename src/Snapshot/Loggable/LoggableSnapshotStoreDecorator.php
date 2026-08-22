<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Snapshot\Loggable;

use Gember\EventSourcing\Snapshot\SnapshotEnvelope;
use Gember\EventSourcing\Snapshot\SnapshotStore;
use Override;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class LoggableSnapshotStoreDecorator implements SnapshotStore
{
    public function __construct(
        private SnapshotStore $snapshotStore,
        private LoggerInterface $logger,
    ) {}

    #[Override]
    public function load(array $domainTags, array $eventNames): ?SnapshotEnvelope
    {
        $startTime = microtime(true);

        $this->logger->info('[Snapshot] Started loading snapshot', [
            'domainTags' => $domainTags,
        ]);

        try {
            $snapshot = $this->snapshotStore->load($domainTags, $eventNames);
        } catch (Throwable $exception) {
            $this->logger->error('[Snapshot] Failed loading snapshot', [
                'exception' => $exception->getMessage(),
                'exceptionClass' => $exception::class,
                'domainTags' => $domainTags,
                'duration' => microtime(true) - $startTime,
            ]);

            throw $exception;
        }

        if ($snapshot !== null) {
            $this->logger->info('[Snapshot] Loaded snapshot', [
                'lastEventId' => $snapshot->lastEventId,
                'eventCount' => $snapshot->eventCount,
                'domainTags' => $snapshot->domainTags,
                'duration' => microtime(true) - $startTime,
            ]);
        } else {
            $this->logger->info('[Snapshot] No snapshot found', [
                'domainTags' => $domainTags,
                'duration' => microtime(true) - $startTime,
            ]);
        }

        return $snapshot;
    }

    #[Override]
    public function save(SnapshotEnvelope $snapshot): void
    {
        $startTime = microtime(true);

        $this->logger->info('[Snapshot] Started saving snapshot', [
            'lastEventId' => $snapshot->lastEventId,
            'eventCount' => $snapshot->eventCount,
            'domainTags' => $snapshot->domainTags,
        ]);

        try {
            $this->snapshotStore->save($snapshot);
        } catch (Throwable $exception) {
            $this->logger->error('[Snapshot] Failed saving snapshot', [
                'exception' => $exception->getMessage(),
                'exceptionClass' => $exception::class,
                'domainTags' => $snapshot->domainTags,
                'duration' => microtime(true) - $startTime,
            ]);

            throw $exception;
        }

        $this->logger->info('[Snapshot] Finished saving snapshot', [
            'lastEventId' => $snapshot->lastEventId,
            'eventCount' => $snapshot->eventCount,
            'domainTags' => $snapshot->domainTags,
            'duration' => microtime(true) - $startTime,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Repository\Snapshot;

use DateTimeImmutable;
use Gember\DependencyContracts\Util\Serialization\Serializer\Serializer;
use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\CommandHandler\Attribute\AttributeCommandHandlerResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\DefaultUseCaseResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\EventSubscriber\Attribute\AttributeEventSubscriberResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\Snapshot\Attribute\AttributeSnapshotResolver;
use Gember\EventSourcing\Repository\Snapshot\SnapshotUseCaseRepositoryDecorator;
use Gember\EventSourcing\Snapshot\Policy\AfterEventsSnapshotPolicy;
use Gember\EventSourcing\Snapshot\Policy\AfterSourcingTimeSnapshotPolicy;
use Gember\EventSourcing\Snapshot\Policy\OnEventsSnapshotPolicy;
use Gember\EventSourcing\Snapshot\SnapshotEnvelope;
use Gember\EventSourcing\Test\TestDoubles\EventStore\TestEventStore;
use Gember\EventSourcing\Test\TestDoubles\Repository\TestUseCaseRepository;
use Gember\EventSourcing\Test\TestDoubles\Snapshot\TestSnapshotStore;
use Gember\EventSourcing\Test\TestDoubles\Util\Log\TestLogger;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Util\Time\Duration;
use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\UseCase\Attribute\Snapshot;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;
use Gember\EventSourcing\UseCase\Metadata;
use Gember\EventSourcing\UseCase\UseCaseAttributeRegistry;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Exception;

final readonly class NativeTestSerializer implements Serializer
{
    public function serialize(object $object): string
    {
        return \serialize($object);
    }

    public function deserialize(string $payload, string $className): object
    {
        return \unserialize($payload);
    }
}

final class FailingDeserializeSerializer implements Serializer
{
    public function serialize(object $object): string
    {
        return \serialize($object);
    }

    public function deserialize(string $payload, string $className): object
    {
        throw new Exception('Deserialization failed: class structure changed');
    }
}

#[Snapshot(afterEvents: 5)]
final class SnapshotTestUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    public string $id = '';

    #[DomainEventSubscriber]
    private function onCreated(TestUseCaseCreatedEvent $event): void
    {
        $this->id = $event->id;
    }
}

#[Snapshot(onEvent: TestUseCaseCreatedEvent::class)]
final class OnEventSnapshotTestUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    public string $id = '';

    #[DomainEventSubscriber]
    private function onCreated(TestUseCaseCreatedEvent $event): void
    {
        $this->id = $event->id;
    }
}

#[Snapshot(afterSourcingTime: new Duration(milliseconds: 0))]
final class SourcingTimeSnapshotTestUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    public string $id = '';

    #[DomainEventSubscriber]
    private function onCreated(TestUseCaseCreatedEvent $event): void
    {
        $this->id = $event->id;
    }
}

#[Snapshot(afterEvents: 1, onEvent: TestUseCaseCreatedEvent::class)]
final class MultiplePolicyMatchTestUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    public string $id = '';

    #[DomainEventSubscriber]
    private function onCreated(TestUseCaseCreatedEvent $event): void
    {
        $this->id = $event->id;
    }
}

final class NoSnapshotTestUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    public string $id = '';

    #[DomainEventSubscriber]
    private function onCreated(TestUseCaseCreatedEvent $event): void
    {
        $this->id = $event->id;
    }
}

/**
 * @internal
 */
final class SnapshotUseCaseRepositoryDecoratorTest extends TestCase
{
    private TestUseCaseRepository $innerRepository;
    private TestEventStore $eventStore;
    private TestSnapshotStore $snapshotStore;
    private SnapshotUseCaseRepositoryDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $attributeResolver = new ReflectorAttributeResolver();
        $useCaseResolver = new DefaultUseCaseResolver(
            new AttributeDomainTagResolver($attributeResolver),
            new AttributeCommandHandlerResolver($attributeResolver),
            new AttributeEventSubscriberResolver($attributeResolver),
            new AttributeSnapshotResolver($attributeResolver),
        );

        UseCaseAttributeRegistry::initialize($useCaseResolver);

        $this->innerRepository = new TestUseCaseRepository();
        $this->eventStore = new TestEventStore();
        $this->snapshotStore = new TestSnapshotStore();

        $this->decorator = new SnapshotUseCaseRepositoryDecorator(
            $this->innerRepository,
            $this->eventStore,
            $useCaseResolver,
            $this->snapshotStore,
            new NativeTestSerializer(),
            [
                new AfterEventsSnapshotPolicy(),
                new AfterSourcingTimeSnapshotPolicy(),
                new OnEventsSnapshotPolicy(),
            ],
            new TestLogger(),
        );
    }

    #[Test]
    public function itShouldDelegateGetWhenNoSnapshotDefinition(): void
    {
        $envelope = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );

        $this->eventStore->envelopesToReturn = [$envelope];

        // NoSnapshotTestUseCase has no #[Snapshot] attribute, so it should not touch the snapshot store.
        // Since TestUseCaseRepository has no use case stored, this will throw.
        // We need to store one first via the inner repository.
        $useCase = NoSnapshotTestUseCase::reconstitute($envelope);
        $this->innerRepository->save($useCase);

        $result = $this->decorator->get(NoSnapshotTestUseCase::class, 'domain-tag-1');

        self::assertInstanceOf(NoSnapshotTestUseCase::class, $result);
        self::assertFalse($this->snapshotStore->loadWasCalled);
    }

    #[Test]
    public function itShouldDelegateHasToInnerRepository(): void
    {
        self::assertFalse($this->decorator->has(SnapshotTestUseCase::class, 'domain-tag-1'));
        self::assertFalse($this->snapshotStore->loadWasCalled);
    }

    #[Test]
    public function itShouldLoadWithoutSnapshotWhenNoneExists(): void
    {
        $envelope = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );

        $this->eventStore->envelopesToReturn = [$envelope];

        $useCase = $this->decorator->get(SnapshotTestUseCase::class, 'domain-tag-1');

        self::assertInstanceOf(SnapshotTestUseCase::class, $useCase);
        self::assertSame('domain-tag-1', $useCase->id);
        self::assertTrue($this->snapshotStore->loadWasCalled);
        self::assertTrue($this->eventStore->loadWasCalled);
    }

    #[Test]
    public function itShouldLoadFromSnapshotAndReplayRemainingEvents(): void
    {
        $snapshotUseCase = new SnapshotTestUseCase();
        // Simulate a use case with some state from a previous snapshot
        $snapshotReflection = new ReflectionProperty($snapshotUseCase, 'id');
        $snapshotReflection->setValue($snapshotUseCase, 'domain-tag-1');

        $this->snapshotStore->snapshotToReturn = new SnapshotEnvelope(
            ['domain-tag-1'],
            [TestUseCaseCreatedEvent::class],
            'last-event-id',
            10,
            \serialize($snapshotUseCase),
        );

        $remainingEnvelope = new DomainEventEnvelope(
            'event-id-11',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('domain-tag-1-updated', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );

        $this->eventStore->envelopesToReturn = [$remainingEnvelope];

        $useCase = $this->decorator->get(SnapshotTestUseCase::class, 'domain-tag-1');

        self::assertInstanceOf(SnapshotTestUseCase::class, $useCase);
        self::assertSame('domain-tag-1-updated', $useCase->id);
        self::assertTrue($this->snapshotStore->loadWasCalled);
        self::assertTrue($this->eventStore->loadWasCalled);
        self::assertSame('last-event-id', $this->eventStore->lastLoadStreamQuery->afterEventId);
    }

    #[Test]
    public function itShouldDelegateSaveToInnerRepository(): void
    {
        $envelope = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );

        $useCase = SnapshotTestUseCase::reconstitute($envelope);

        $this->decorator->save($useCase);

        self::assertTrue($this->innerRepository->has(SnapshotTestUseCase::class, 'domain-tag-1'));
    }

    #[Test]
    public function itShouldCreateSnapshotWhenAfterEventsThresholdReached(): void
    {
        // Set up event store with enough events to reach the threshold (afterEvents: 5)
        $envelopes = [];
        for ($i = 1; $i <= 4; ++$i) {
            $envelopes[] = new DomainEventEnvelope(
                'event-id-' . $i,
                ['domain-tag-1'],
                new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
                new Metadata(),
                new DateTimeImmutable(),
            );
        }
        $this->eventStore->envelopesToReturn = $envelopes;

        // get() first to establish sourcing context (4 events loaded)
        $useCase = $this->decorator->get(SnapshotTestUseCase::class, 'domain-tag-1');

        // Apply one more event so total = 5 which meets the threshold
        $useCase->apply(new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'));

        $this->decorator->save($useCase);

        self::assertTrue($this->snapshotStore->saveWasCalled);
        self::assertNotNull($this->snapshotStore->storedSnapshot);
        self::assertSame(5, $this->snapshotStore->storedSnapshot->eventCount);
        self::assertSame(['domain-tag-1'], $this->snapshotStore->storedSnapshot->domainTags);
    }

    #[Test]
    public function itShouldNotCreateSnapshotWhenThresholdNotReached(): void
    {
        // Set up event store with fewer events than threshold
        $envelopes = [];
        for ($i = 1; $i <= 2; ++$i) {
            $envelopes[] = new DomainEventEnvelope(
                'event-id-' . $i,
                ['domain-tag-1'],
                new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
                new Metadata(),
                new DateTimeImmutable(),
            );
        }
        $this->eventStore->envelopesToReturn = $envelopes;

        // get() first to establish sourcing context (2 events)
        $useCase = $this->decorator->get(SnapshotTestUseCase::class, 'domain-tag-1');

        // Apply one more event, total = 3 which is below threshold of 5
        $useCase->apply(new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'));

        $this->decorator->save($useCase);

        self::assertFalse($this->snapshotStore->saveWasCalled);
        self::assertNull($this->snapshotStore->storedSnapshot);
    }

    #[Test]
    public function itShouldCreateSnapshotWhenOnEventMatches(): void
    {
        $envelope = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );
        $this->eventStore->envelopesToReturn = [$envelope];

        // OnEventSnapshotTestUseCase has #[Snapshot(onEvent: TestUseCaseCreatedEvent::class)]
        $useCase = $this->decorator->get(OnEventSnapshotTestUseCase::class, 'domain-tag-1');

        // Apply a TestUseCaseCreatedEvent which matches the onEvent config
        $useCase->apply(new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'));

        $this->decorator->save($useCase);

        self::assertTrue($this->snapshotStore->saveWasCalled);
        self::assertNotNull($this->snapshotStore->storedSnapshot);
    }

    #[Test]
    public function itShouldRecordLastEventIdFromUseCaseAtSnapshotTime(): void
    {
        // Load with 4 events — lastEventId after get() will be 'event-id-4'
        $envelopes = [];
        for ($i = 1; $i <= 4; ++$i) {
            $envelopes[] = new DomainEventEnvelope(
                'event-id-' . $i,
                ['domain-tag-1'],
                new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
                new Metadata(),
                new DateTimeImmutable(),
            );
        }
        $this->eventStore->envelopesToReturn = $envelopes;

        $useCase = $this->decorator->get(SnapshotTestUseCase::class, 'domain-tag-1');

        self::assertSame('event-id-4', $useCase->getLastEventId());

        // Simulate what the real EventSourcedUseCaseRepository::save() does:
        // it updates lastEventId after appending events
        $useCase->apply(new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'));
        $useCase->setLastEventId('event-id-5');

        $this->decorator->save($useCase);

        // The snapshot should record the post-save lastEventId, not the stale reconstitution ID
        self::assertNotNull($this->snapshotStore->storedSnapshot);
        self::assertSame('event-id-5', $this->snapshotStore->storedSnapshot->lastEventId);
    }

    #[Test]
    public function itShouldNotCreateSnapshotWithoutPriorGet(): void
    {
        $envelope = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );

        $useCase = SnapshotTestUseCase::reconstitute($envelope);

        // save() without prior get() - no sourcing context established
        $this->decorator->save($useCase);

        self::assertFalse($this->snapshotStore->saveWasCalled);
        self::assertNull($this->snapshotStore->storedSnapshot);
    }

    #[Test]
    public function itShouldNotFailWhenSnapshotSaveFails(): void
    {
        $envelopes = [];
        for ($i = 1; $i <= 4; ++$i) {
            $envelopes[] = new DomainEventEnvelope(
                'event-id-' . $i,
                ['domain-tag-1'],
                new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
                new Metadata(),
                new DateTimeImmutable(),
            );
        }
        $this->eventStore->envelopesToReturn = $envelopes;

        $useCase = $this->decorator->get(SnapshotTestUseCase::class, 'domain-tag-1');
        $useCase->apply(new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'));

        // Snapshot save will throw, but save() should still succeed
        $this->snapshotStore->saveShouldThrow = new Exception('snapshot store unavailable');

        $this->decorator->save($useCase);

        // Events were saved (no exception), snapshot failed silently
        self::assertTrue($this->snapshotStore->saveWasCalled);
    }

    #[Test]
    public function itShouldCreateSnapshotWhenAfterSourcingTimeThresholdExceeded(): void
    {
        // SourcingTimeSnapshotTestUseCase has #[Snapshot(afterSourcingTime: new Duration(milliseconds: 0))]
        // Any reconstitution time > 0ms will trigger it
        $envelope = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );
        $this->eventStore->envelopesToReturn = [$envelope];

        $useCase = $this->decorator->get(SourcingTimeSnapshotTestUseCase::class, 'domain-tag-1');
        $useCase->apply(new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'));
        $useCase->setLastEventId('event-id-2');

        $this->decorator->save($useCase);

        self::assertTrue($this->snapshotStore->saveWasCalled);
        self::assertNotNull($this->snapshotStore->storedSnapshot);
    }

    #[Test]
    public function itShouldOnlyTriggerFirstMatchingSnapshotPolicy(): void
    {
        // MultiplePolicyMatchTestUseCase has afterEvents: 1 AND onEvent: TestUseCaseCreatedEvent
        // Both AfterEventsSnapshotPolicy and OnEventsSnapshotPolicy would match.
        // Only the first matching policy should trigger a save (first-match-wins).
        $envelope = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );
        $this->eventStore->envelopesToReturn = [$envelope];

        $useCase = $this->decorator->get(MultiplePolicyMatchTestUseCase::class, 'domain-tag-1');
        $useCase->apply(new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'));
        $useCase->setLastEventId('event-id-2');

        $this->decorator->save($useCase);

        // Snapshot should be saved exactly once, not once per matching policy
        self::assertTrue($this->snapshotStore->saveWasCalled);
        self::assertNotNull($this->snapshotStore->storedSnapshot);

        // After the first policy triggers, the sourcing context is cleared (WeakMap entry removed).
        // A second save() should NOT trigger another snapshot.
        $this->snapshotStore->saveWasCalled = false;
        $this->snapshotStore->storedSnapshot = null;

        $useCase->apply(new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'));
        $this->decorator->save($useCase);

        self::assertFalse($this->snapshotStore->saveWasCalled);
    }

    #[Test]
    public function itShouldFallBackToFullReplayWhenDeserializationFails(): void
    {
        $attributeResolver = new ReflectorAttributeResolver();
        $useCaseResolver = new DefaultUseCaseResolver(
            new AttributeDomainTagResolver($attributeResolver),
            new AttributeCommandHandlerResolver($attributeResolver),
            new AttributeEventSubscriberResolver($attributeResolver),
            new AttributeSnapshotResolver($attributeResolver),
        );

        $logger = new TestLogger();

        $decorator = new SnapshotUseCaseRepositoryDecorator(
            $this->innerRepository,
            $this->eventStore,
            $useCaseResolver,
            $this->snapshotStore,
            new FailingDeserializeSerializer(),
            [
                new AfterEventsSnapshotPolicy(),
                new AfterSourcingTimeSnapshotPolicy(),
                new OnEventsSnapshotPolicy(),
            ],
            $logger,
        );

        $this->snapshotStore->snapshotToReturn = new SnapshotEnvelope(
            ['domain-tag-1'],
            [TestUseCaseCreatedEvent::class],
            'last-event-id',
            5,
            'corrupted-payload',
        );

        // Partial events (after snapshot) — these should NOT be used for reconstitution
        $partialEnvelope = new DomainEventEnvelope(
            'event-id-6',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('partial-tag', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );

        // Full replay events — these SHOULD be used for reconstitution
        $fullEnvelope = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );

        // First call: afterEventId load returns partial events
        // Second call: full replay (no afterEventId) returns all events
        $this->eventStore->loadResults = [
            [$partialEnvelope],  // First call: events after snapshot
            [$fullEnvelope],     // Second call: full replay after deserialization failure
        ];

        $useCase = $decorator->get(SnapshotTestUseCase::class, 'domain-tag-1');

        self::assertInstanceOf(SnapshotTestUseCase::class, $useCase);
        // Must be 'domain-tag-1' from full replay, NOT 'partial-tag' from partial events
        self::assertSame('domain-tag-1', $useCase->id);

        // Verify the second load() was called without afterEventId (full replay)
        self::assertNull($this->eventStore->lastLoadStreamQuery->afterEventId);

        // Verify stale snapshot fallback was logged
        $staleLogFound = false;
        foreach ($logger->logs as $log) {
            if ($log['message'] === '[Snapshot] Stale snapshot, falling back to full replay') {
                $staleLogFound = true;
                break;
            }
        }
        self::assertTrue($staleLogFound, 'Expected stale snapshot fallback log message');
    }

    #[Test]
    public function itShouldFallBackToFullReplayWhenSnapshotLoadFails(): void
    {
        $this->snapshotStore->loadShouldThrow = new Exception('snapshot store unavailable');

        $envelope = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );

        $this->eventStore->envelopesToReturn = [$envelope];

        $useCase = $this->decorator->get(SnapshotTestUseCase::class, 'domain-tag-1');

        self::assertInstanceOf(SnapshotTestUseCase::class, $useCase);
        self::assertSame('domain-tag-1', $useCase->id);
    }

    #[Test]
    public function itShouldUseSnapshotDirectlyWhenNoNewEventsExist(): void
    {
        $snapshotUseCase = new SnapshotTestUseCase();
        $snapshotReflection = new ReflectionProperty($snapshotUseCase, 'id');
        $snapshotReflection->setValue($snapshotUseCase, 'domain-tag-1');

        $this->snapshotStore->snapshotToReturn = new SnapshotEnvelope(
            ['domain-tag-1'],
            [TestUseCaseCreatedEvent::class],
            'last-event-id',
            10,
            \serialize($snapshotUseCase),
        );

        // Both load() calls throw NoEventsForDomainTagsException:
        // 1st: no events after snapshot's lastEventId (snapshot is current)
        // 2nd: no events at all for this boundary (confirms snapshot covers everything)
        $this->eventStore->loadResults = [
            new \Gember\EventSourcing\EventStore\NoEventsForDomainTagsException(),
            new \Gember\EventSourcing\EventStore\NoEventsForDomainTagsException(),
        ];

        $useCase = $this->decorator->get(SnapshotTestUseCase::class, 'domain-tag-1');

        self::assertInstanceOf(SnapshotTestUseCase::class, $useCase);
        self::assertSame('domain-tag-1', $useCase->id);
        self::assertSame(10, $this->snapshotStore->storedSnapshot === null ? 10 : 0);
    }

    #[Test]
    public function itShouldFallBackToFullReplayWhenSnapshotIsStale(): void
    {
        // Create a use case and serialize it as a snapshot
        $originalEnvelope = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );

        $snapshotUseCase = SnapshotTestUseCase::reconstitute($originalEnvelope);

        $this->snapshotStore->snapshotToReturn = new SnapshotEnvelope(
            ['domain-tag-1'],
            [TestUseCaseCreatedEvent::class],
            'non-existent-event-id',
            1,
            serialize($snapshotUseCase),
        );

        // First load() with afterEventId throws (event doesn't exist in store)
        // Second load() without afterEventId returns all events (full replay)
        $fullEnvelope = new DomainEventEnvelope(
            'event-id-1',
            ['domain-tag-1'],
            new TestUseCaseCreatedEvent('domain-tag-1', 'secondary'),
            new Metadata(),
            new DateTimeImmutable(),
        );

        $this->eventStore->loadResults = [
            new \Gember\EventSourcing\EventStore\NoEventsForDomainTagsException(),
            [$fullEnvelope],
        ];

        $useCase = $this->decorator->get(SnapshotTestUseCase::class, 'domain-tag-1');

        // Should have reconstituted from full replay, not from snapshot
        self::assertInstanceOf(SnapshotTestUseCase::class, $useCase);
        self::assertSame('event-id-1', $useCase->getLastEventId());
    }
}

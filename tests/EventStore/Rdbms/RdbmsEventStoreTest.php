<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\EventStore\Rdbms;

use Gember\DependencyContracts\EventStore\Rdbms\RdbmsEvent;
use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\SagaId\Attribute\AttributeSagaIdResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\DefaultDomainEventResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Attribute\AttributeEventNameResolver;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\Metadata;
use Gember\EventSourcing\EventStore\EventStoreFailedException;
use Gember\EventSourcing\EventStore\NoEventsForDomainTagsException;
use Gember\EventSourcing\EventStore\OptimisticLockException;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsDomainEventEnvelopeFactory;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsEventFactory;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsEventStore;
use Gember\EventSourcing\EventStore\StreamQuery;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\EventStore\Rdbms\TestRdbmsEventStoreRepository;
use Gember\EventSourcing\Test\TestDoubles\Registry\TestEventRegistry;
use Gember\EventSourcing\Test\TestDoubles\Util\Serialization\Serializer\TestSerializer;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use Exception;
use DateTimeImmutable;
use stdClass;

/**
 * @internal
 */
final class RdbmsEventStoreTest extends TestCase
{
    private TestRdbmsEventStoreRepository $eventStoreRepository;
    private RdbmsEventStore $eventStore;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventStore = new RdbmsEventStore(
            $domainEventResolver = new DefaultDomainEventResolver(
                new AttributeEventNameResolver($attributeResolver = new ReflectorAttributeResolver()),
                new AttributeDomainTagResolver($attributeResolver),
                new AttributeSagaIdResolver($attributeResolver),
            ),
            new RdbmsDomainEventEnvelopeFactory(
                $serializer = new TestSerializer(),
                new TestEventRegistry(),
            ),
            new RdbmsEventFactory($domainEventResolver, $serializer),
            $this->eventStoreRepository = new TestRdbmsEventStoreRepository(),
        );
    }

    #[Test]
    public function itShouldThrowExceptionWhenLoadEventsFailed(): void
    {
        $this->eventStoreRepository->throwException = new Exception('Event store repository failed');

        self::expectException(EventStoreFailedException::class);
        self::expectExceptionMessage('Event store repository failed');

        $this->eventStore->load(new StreamQuery(
            [
                '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
            ],
            [
                TestUseCaseCreatedEvent::class,
            ],
        ));
    }

    #[Test]
    public function itShouldThrowExceptionWhenNoEventsAreFound(): void
    {
        self::expectException(NoEventsForDomainTagsException::class);

        $this->eventStore->load(new StreamQuery(
            [
                '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
            ],
            [
                TestUseCaseCreatedEvent::class,
            ],
        ));
    }

    #[Test]
    public function itShouldLoadEnvelopes(): void
    {
        $this->eventStoreRepository->events = [
            new RdbmsEvent(
                'c6b7b4f7-0f3b-40dc-8021-e16ef4c64759',
                [
                    '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
                    '1b34e6a1-bfde-4995-a3d2-1aac53f6b124',
                ],
                'test.use-case.created',
                '',
                [],
                $appliedAt1 = new DateTimeImmutable(),
            ),
            new RdbmsEvent(
                '97faca2a-5b97-4dc0-a21e-a9dac5bff98e',
                [
                    '9b71b0b4-2c17-493a-980f-6d8d29182e15',
                ],
                'test.use-case.modified',
                '',
                [],
                $appliedAt2 = new DateTimeImmutable(),
            ),
        ];

        $events = $this->eventStore->load(new StreamQuery(
            [
                '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
            ],
            [
                TestUseCaseCreatedEvent::class,
            ],
        ));

        self::assertEquals([
            new DomainEventEnvelope(
                'c6b7b4f7-0f3b-40dc-8021-e16ef4c64759',
                [
                    '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
                    '1b34e6a1-bfde-4995-a3d2-1aac53f6b124',
                ],
                new stdClass(),
                new Metadata(),
                $appliedAt1,
            ),
            new DomainEventEnvelope(
                '97faca2a-5b97-4dc0-a21e-a9dac5bff98e',
                [
                    '9b71b0b4-2c17-493a-980f-6d8d29182e15',
                ],
                new stdClass(),
                new Metadata(),
                $appliedAt2,
            ),
        ], $events);
    }

    #[Test]
    public function itShouldDiscardAppendWhenThereAreNoEvents(): void
    {
        $this->eventStore->append(new StreamQuery(
            [
                '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
            ],
            [
                TestUseCaseCreatedEvent::class,
            ],
        ), null);

        self::assertSame([], $this->eventStoreRepository->getEvents(
            [
                '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
            ],
            [
                TestUseCaseCreatedEvent::class,
            ],
        ));
    }

    #[Test]
    public function itShouldGuardOptimisticLock(): void
    {
        $this->eventStoreRepository->lastEventIdPersisted = 'dccda5e4-ed20-4a98-b571-02ae7e71e8be';

        self::expectException(OptimisticLockException::class);

        $this->eventStore->append(
            new StreamQuery(
                [
                    '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
                ],
                [
                    TestUseCaseCreatedEvent::class,
                ],
            ),
            '0e1647ef-8c62-477e-9957-d7b962876eff',
            new DomainEventEnvelope(
                'c6b7b4f7-0f3b-40dc-8021-e16ef4c64759',
                [
                    '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
                    '1b34e6a1-bfde-4995-a3d2-1aac53f6b124',
                ],
                new stdClass(),
                new Metadata(),
                new DateTimeImmutable(),
            ),
            new DomainEventEnvelope(
                '97faca2a-5b97-4dc0-a21e-a9dac5bff98e',
                [
                    '9b71b0b4-2c17-493a-980f-6d8d29182e15',
                ],
                new stdClass(),
                new Metadata(),
                new DateTimeImmutable(),
            ),
        );
    }

    #[Test]
    public function itShouldThrowEventStoreFailedExceptionWhenSomeUnexpectedErrorHappenedOnGuardOptimisticLock(): void
    {
        $this->eventStoreRepository->lastEventIdPersisted = 'dccda5e4-ed20-4a98-b571-02ae7e71e8be';

        self::expectException(EventStoreFailedException::class);

        $this->eventStore->append(
            new StreamQuery(
                [
                    '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
                ],
                [
                    TestUseCaseModifiedEvent::class,
                ],
            ),
            '0e1647ef-8c62-477e-9957-d7b962876eff',
            new DomainEventEnvelope(
                'c6b7b4f7-0f3b-40dc-8021-e16ef4c64759',
                [
                    '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
                    '1b34e6a1-bfde-4995-a3d2-1aac53f6b124',
                ],
                new stdClass(),
                new Metadata(),
                new DateTimeImmutable(),
            ),
            new DomainEventEnvelope(
                '97faca2a-5b97-4dc0-a21e-a9dac5bff98e',
                [
                    '9b71b0b4-2c17-493a-980f-6d8d29182e15',
                ],
                new stdClass(),
                new Metadata(),
                new DateTimeImmutable(),
            ),
        );
    }

    #[Test]
    public function itShouldThrowExceptionWhenAppendEventsFailed(): void
    {
        $this->eventStoreRepository->lastEventIdPersisted = '0e1647ef-8c62-477e-9957-d7b962876eff';
        $this->eventStoreRepository->throwException = new Exception('Save failed');

        self::expectException(EventStoreFailedException::class);
        self::expectExceptionMessage('Save failed');

        $this->eventStore->append(
            new StreamQuery(
                [
                    '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
                ],
                [
                    TestUseCaseCreatedEvent::class,
                ],
            ),
            '0e1647ef-8c62-477e-9957-d7b962876eff',
            new DomainEventEnvelope(
                'c6b7b4f7-0f3b-40dc-8021-e16ef4c64759',
                [
                    '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
                    '1b34e6a1-bfde-4995-a3d2-1aac53f6b124',
                ],
                new TestUseCaseCreatedEvent('5ae5484d-9890-4bbe-95e8-b828bfea2f9e', '1b34e6a1-bfde-4995-a3d2-1aac53f6b124'),
                new Metadata(),
                new DateTimeImmutable(),
            ),
            new DomainEventEnvelope(
                '97faca2a-5b97-4dc0-a21e-a9dac5bff98e',
                [
                    '9b71b0b4-2c17-493a-980f-6d8d29182e15',
                    '8784010b-b13c-4c3d-869a-80b1790f0122',
                ],
                new TestUseCaseCreatedEvent('9b71b0b4-2c17-493a-980f-6d8d29182e15', '8784010b-b13c-4c3d-869a-80b1790f0122'),
                new Metadata(),
                new DateTimeImmutable(),
            ),
        );
    }

    #[Test]
    public function itShouldAppendEvents(): void
    {
        $this->eventStoreRepository->lastEventIdPersisted = '0e1647ef-8c62-477e-9957-d7b962876eff';

        $this->eventStore->append(
            new StreamQuery(
                [
                    '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
                ],
                [
                    TestUseCaseCreatedEvent::class,
                ],
            ),
            '0e1647ef-8c62-477e-9957-d7b962876eff',
            new DomainEventEnvelope(
                'c6b7b4f7-0f3b-40dc-8021-e16ef4c64759',
                [
                    '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
                    '1b34e6a1-bfde-4995-a3d2-1aac53f6b124',
                ],
                new TestUseCaseCreatedEvent('5ae5484d-9890-4bbe-95e8-b828bfea2f9e', '1b34e6a1-bfde-4995-a3d2-1aac53f6b124'),
                new Metadata(),
                $appliedAt1 = new DateTimeImmutable(),
            ),
            new DomainEventEnvelope(
                '97faca2a-5b97-4dc0-a21e-a9dac5bff98e',
                [
                    '9b71b0b4-2c17-493a-980f-6d8d29182e15',
                    '8784010b-b13c-4c3d-869a-80b1790f0122',
                ],
                new TestUseCaseCreatedEvent('9b71b0b4-2c17-493a-980f-6d8d29182e15', '8784010b-b13c-4c3d-869a-80b1790f0122'),
                new Metadata(),
                $appliedAt2 = new DateTimeImmutable(),
            ),
        );

        self::assertEquals([
            new RdbmsEvent(
                'c6b7b4f7-0f3b-40dc-8021-e16ef4c64759',
                [
                    '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
                    '1b34e6a1-bfde-4995-a3d2-1aac53f6b124',
                ],
                'test.use-case.created',
                'O:69:"Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent":2:{s:2:"id";s:36:"5ae5484d-9890-4bbe-95e8-b828bfea2f9e";s:11:"secondaryId";s:36:"1b34e6a1-bfde-4995-a3d2-1aac53f6b124";}',
                [],
                $appliedAt1,
            ),
            new RdbmsEvent(
                '97faca2a-5b97-4dc0-a21e-a9dac5bff98e',
                [
                    '9b71b0b4-2c17-493a-980f-6d8d29182e15',
                    '8784010b-b13c-4c3d-869a-80b1790f0122',
                ],
                'test.use-case.created',
                'O:69:"Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent":2:{s:2:"id";s:36:"9b71b0b4-2c17-493a-980f-6d8d29182e15";s:11:"secondaryId";s:36:"8784010b-b13c-4c3d-869a-80b1790f0122";}',
                [],
                $appliedAt2,
            ),
        ], $this->eventStoreRepository->getEvents(
            [
                '5ae5484d-9890-4bbe-95e8-b828bfea2f9e',
            ],
            [
                TestUseCaseCreatedEvent::class,
            ],
        ));
    }
}

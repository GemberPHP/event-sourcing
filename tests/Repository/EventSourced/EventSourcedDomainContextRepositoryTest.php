<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Repository\EventSourced;

use Gember\EventSourcing\DomainContext\DomainContextAttributeRegistry;
use Gember\EventSourcing\EventStore\DomainEventEnvelopeFactory;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsDomainEventEnvelopeFactory;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsEvent;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsEventFactory;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsEventStore;
use Gember\EventSourcing\Repository\DomainContextNotFoundException;
use Gember\EventSourcing\Repository\EventSourced\EventSourcedDomainContextRepository;
use Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties\Attribute\AttributeDomainIdPropertiesResolver;
use Gember\EventSourcing\Resolver\DomainContext\SubscribedEvents\Attribute\AttributeSubscribedEventsResolver;
use Gember\EventSourcing\Resolver\DomainContext\SubscriberMethodForEvent\Attribute\AttributeSubscriberMethodForEventResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\Attribute\AttributeDomainIdsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\Interface\InterfaceDomainIdsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\Stacked\StackedDomainIdsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Attribute\AttributeNormalizedEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Interface\InterfaceNormalizedEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Stacked\StackedNormalizedEventNameResolver;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContext;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainId;
use Gember\EventSourcing\Test\TestDoubles\EventStore\Rdbms\TestRdbmsEventStoreRepository;
use Gember\EventSourcing\Test\TestDoubles\Registry\TestEventRegistry;
use Gember\EventSourcing\Test\TestDoubles\Util\Generator\Identity\TestIdentityGenerator;
use Gember\EventSourcing\Test\TestDoubles\Util\Messaging\MessageBus\TestEventBus;
use Gember\EventSourcing\Test\TestDoubles\Util\Serialization\Serializer\TestSerializer;
use Gember\EventSourcing\Test\TestDoubles\Util\Time\Clock\TestClock;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use DateTimeImmutable;

/**
 * @internal
 */
final class EventSourcedDomainContextRepositoryTest extends TestCase
{
    // @phpstan-ignore-next-line
    private EventSourcedDomainContextRepository $repository;
    private TestRdbmsEventStoreRepository $eventStoreRepository;
    private TestClock $clock;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        DomainContextAttributeRegistry::initialize(
            new AttributeDomainIdPropertiesResolver($attributeResolver = new ReflectorAttributeResolver()),
            new AttributeSubscriberMethodForEventResolver($attributeResolver),
        );

        $this->clock = new TestClock();
        $this->clock->time = new DateTimeImmutable('2024-10-12 12:00:30');

        $this->repository = new EventSourcedDomainContextRepository(
            new RdbmsEventStore(
                $eventNameResolver = new StackedNormalizedEventNameResolver([
                    new AttributeNormalizedEventNameResolver(
                        $attributeResolver,
                    ),
                    new InterfaceNormalizedEventNameResolver(),
                ]),
                new RdbmsDomainEventEnvelopeFactory(
                    $serializer = new TestSerializer(),
                    new TestEventRegistry(),
                ),
                new RdbmsEventFactory(
                    $eventNameResolver,
                    $serializer,
                ),
                $this->eventStoreRepository = new TestRdbmsEventStoreRepository(),
            ),
            new DomainEventEnvelopeFactory(
                new StackedDomainIdsResolver([
                    new AttributeDomainIdsResolver($attributeResolver),
                    new InterfaceDomainIdsResolver(),
                ]),
                new TestIdentityGenerator(),
                $this->clock,
            ),
            new AttributeSubscribedEventsResolver($attributeResolver),
            new TestEventBus(),
        );
    }

    #[Test]
    public function itShouldThrowExceptionWhenDomainContextIsNotFound(): void
    {
        self::expectException(DomainContextNotFoundException::class);

        $this->repository->get(TestDomainContext::class, 'f59f7b00-051c-4e31-aa48-c332601aaebc');
    }

    #[Test]
    public function itShouldGetDomainContext(): void
    {
        $this->eventStoreRepository->events = [
            new RdbmsEvent(
                '7fe1d158-11d3-4f41-a4d4-4f8e19c721fb',
                [
                    'f59f7b00-051c-4e31-aa48-c332601aaebc',
                    '802360e7-143e-439e-a797-fec599cf452f',
                ],
                'test.domain-context.created',
                '',
                [],
                new DateTimeImmutable(),
            ),
            new RdbmsEvent(
                '5c2b6d7e-2cb9-4c3c-b6fe-90e141ebaded',
                [
                    'f59f7b00-051c-4e31-aa48-c332601aaebc',
                ],
                'test.domain-context.modified',
                '',
                [],
                new DateTimeImmutable(),
            ),
        ];

        $domainContext = $this->repository->get(TestDomainContext::class, 'f59f7b00-051c-4e31-aa48-c332601aaebc', '802360e7-143e-439e-a797-fec599cf452f');

        self::assertInstanceOf(TestDomainContext::class, $domainContext);
    }

    #[Test]
    public function itShouldReturnIfRepositoryHasDomainContext(): void
    {
        self::assertFalse($this->repository->has(TestDomainContext::class, 'f59f7b00-051c-4e31-aa48-c332601aaebc'));

        $this->eventStoreRepository->events = [
            new RdbmsEvent(
                '7fe1d158-11d3-4f41-a4d4-4f8e19c721fb',
                [
                    'f59f7b00-051c-4e31-aa48-c332601aaebc',
                    '802360e7-143e-439e-a797-fec599cf452f',
                ],
                'test.domain-context.created',
                '',
                [],
                new DateTimeImmutable(),
            ),
            new RdbmsEvent(
                '5c2b6d7e-2cb9-4c3c-b6fe-90e141ebaded',
                [
                    'f59f7b00-051c-4e31-aa48-c332601aaebc',
                ],
                'test.domain-context.modified',
                '',
                [],
                new DateTimeImmutable(),
            ),
        ];

        self::assertTrue($this->repository->has(TestDomainContext::class, 'f59f7b00-051c-4e31-aa48-c332601aaebc'));
    }

    #[Test]
    public function itShouldSaveDomainContext(): void
    {
        $domainContext = TestDomainContext::create(new TestDomainId('1dcdf55b-f518-419f-abad-768afa56e6bb'), 'ded58226-d3bf-4a7e-a9eb-cc7d7b7603ce');
        $domainContext->modify();

        $this->repository->save($domainContext);

        self::assertEquals([
            new RdbmsEvent(
                'be07b19b-c7ab-429e-a9c3-6b7d942122c0',
                [
                    '1dcdf55b-f518-419f-abad-768afa56e6bb',
                    'ded58226-d3bf-4a7e-a9eb-cc7d7b7603ce',
                ],
                'test.domain-context.created',
                'O:81:"Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent":2:{s:2:"id";s:36:"1dcdf55b-f518-419f-abad-768afa56e6bb";s:11:"secondaryId";s:36:"ded58226-d3bf-4a7e-a9eb-cc7d7b7603ce";}',
                [],
                $this->clock->time,
            ),
            new RdbmsEvent(
                'be07b19b-c7ab-429e-a9c3-6b7d942122c0',
                [
                    '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a',
                    'afb200a7-4f94-4d40-87b2-50575a1553c7',
                ],
                'test.domain-context.modified',
                'O:82:"Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextModifiedEvent":0:{}',
                [],
                $this->clock->time,
            ),
        ], $this->eventStoreRepository->events);
    }
}

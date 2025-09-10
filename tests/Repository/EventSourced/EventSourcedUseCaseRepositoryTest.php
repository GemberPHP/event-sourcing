<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Repository\EventSourced;

use Gember\DependencyContracts\EventStore\Rdbms\RdbmsEvent;
use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\Interface\InterfaceDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\Stacked\StackedDomainTagResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\DefaultDomainEventResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Attribute\AttributeEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\ClassName\ClassNameEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Interface\InterfaceEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Stacked\StackedEventNameResolver;
use Gember\EventSourcing\UseCase\UseCaseAttributeRegistry;
use Gember\EventSourcing\EventStore\DomainEventEnvelopeFactory;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsDomainEventEnvelopeFactory;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsEventFactory;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsEventStore;
use Gember\EventSourcing\Repository\UseCaseNotFoundException;
use Gember\EventSourcing\Repository\EventSourced\EventSourcedUseCaseRepository;
use Gember\EventSourcing\Resolver\UseCase\DomainTagProperties\Attribute\AttributeDomainTagsPropertiesResolver;
use Gember\EventSourcing\Resolver\UseCase\SubscribedEvents\Attribute\AttributeSubscribedEventsResolver;
use Gember\EventSourcing\Resolver\UseCase\SubscriberMethodForEvent\Attribute\AttributeSubscriberMethodForEventResolver;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestDomainTag;
use Gember\EventSourcing\Test\TestDoubles\EventStore\Rdbms\TestRdbmsEventStoreRepository;
use Gember\EventSourcing\Test\TestDoubles\Registry\TestEventRegistry;
use Gember\EventSourcing\Test\TestDoubles\Util\Generator\Identity\TestIdentityGenerator;
use Gember\EventSourcing\Test\TestDoubles\Util\Messaging\MessageBus\TestEventBus;
use Gember\EventSourcing\Test\TestDoubles\Util\Serialization\Serializer\TestSerializer;
use Gember\EventSourcing\Test\TestDoubles\Util\Time\Clock\TestClock;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use DateTimeImmutable;

/**
 * @internal
 */
final class EventSourcedUseCaseRepositoryTest extends TestCase
{
    private EventSourcedUseCaseRepository $repository;
    private TestRdbmsEventStoreRepository $eventStoreRepository;
    private TestClock $clock;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        UseCaseAttributeRegistry::initialize(
            new AttributeDomainTagsPropertiesResolver($attributeResolver = new ReflectorAttributeResolver()),
            new AttributeSubscriberMethodForEventResolver($attributeResolver),
        );

        $this->clock = new TestClock();
        $this->clock->time = new DateTimeImmutable('2024-10-12 12:00:30');

        $this->repository = new EventSourcedUseCaseRepository(
            new RdbmsEventStore(
                $domainEventResolver = new DefaultDomainEventResolver(
                    new StackedEventNameResolver(
                        [
                            new AttributeEventNameResolver($attributeResolver),
                            new InterfaceEventNameResolver(),
                        ],
                        new ClassNameEventNameResolver(new NativeFriendlyClassNamer(new NativeInflector())),
                    ),
                    new StackedDomainTagResolver(
                        [
                            new AttributeDomainTagResolver($attributeResolver),
                            new InterfaceDomainTagResolver(),
                        ],
                    ),
                ),
                new RdbmsDomainEventEnvelopeFactory(
                    $serializer = new TestSerializer(),
                    new TestEventRegistry(),
                ),
                new RdbmsEventFactory(
                    $domainEventResolver,
                    $serializer,
                ),
                $this->eventStoreRepository = new TestRdbmsEventStoreRepository(),
            ),
            new DomainEventEnvelopeFactory(
                new DefaultDomainEventResolver(
                    new StackedEventNameResolver(
                        [
                            new AttributeEventNameResolver($attributeResolver),
                            new InterfaceEventNameResolver(),
                        ],
                        new ClassNameEventNameResolver(new NativeFriendlyClassNamer(new NativeInflector())),
                    ),
                    new StackedDomainTagResolver(
                        [
                            new AttributeDomainTagResolver($attributeResolver),
                            new InterfaceDomainTagResolver(),
                        ],
                    ),
                ),
                new TestIdentityGenerator(),
                $this->clock,
            ),
            new AttributeSubscribedEventsResolver($attributeResolver),
            new TestEventBus(),
        );
    }

    #[Test]
    public function itShouldThrowExceptionWhenUseCaseIsNotFound(): void
    {
        self::expectException(UseCaseNotFoundException::class);

        $this->repository->get(TestUseCase::class, 'f59f7b00-051c-4e31-aa48-c332601aaebc');
    }

    #[Test]
    public function itShouldGetUseCase(): void
    {
        $this->eventStoreRepository->events = [
            new RdbmsEvent(
                '7fe1d158-11d3-4f41-a4d4-4f8e19c721fb',
                [
                    'f59f7b00-051c-4e31-aa48-c332601aaebc',
                    '802360e7-143e-439e-a797-fec599cf452f',
                ],
                'test.use-case.created',
                '',
                [],
                new DateTimeImmutable(),
            ),
            new RdbmsEvent(
                '5c2b6d7e-2cb9-4c3c-b6fe-90e141ebaded',
                [
                    'f59f7b00-051c-4e31-aa48-c332601aaebc',
                ],
                'test.use-case.modified',
                '',
                [],
                new DateTimeImmutable(),
            ),
        ];

        $useCase = $this->repository->get(TestUseCase::class, 'f59f7b00-051c-4e31-aa48-c332601aaebc', '802360e7-143e-439e-a797-fec599cf452f');

        self::assertInstanceOf(TestUseCase::class, $useCase);
    }

    #[Test]
    public function itShouldReturnIfRepositoryHasUseCase(): void
    {
        self::assertFalse($this->repository->has(TestUseCase::class, 'f59f7b00-051c-4e31-aa48-c332601aaebc'));

        $this->eventStoreRepository->events = [
            new RdbmsEvent(
                '7fe1d158-11d3-4f41-a4d4-4f8e19c721fb',
                [
                    'f59f7b00-051c-4e31-aa48-c332601aaebc',
                    '802360e7-143e-439e-a797-fec599cf452f',
                ],
                'test.use-case.created',
                '',
                [],
                new DateTimeImmutable(),
            ),
            new RdbmsEvent(
                '5c2b6d7e-2cb9-4c3c-b6fe-90e141ebaded',
                [
                    'f59f7b00-051c-4e31-aa48-c332601aaebc',
                ],
                'test.use-case.modified',
                '',
                [],
                new DateTimeImmutable(),
            ),
        ];

        self::assertTrue($this->repository->has(TestUseCase::class, 'f59f7b00-051c-4e31-aa48-c332601aaebc'));
    }

    #[Test]
    public function itShouldSaveUseCase(): void
    {
        $useCase = TestUseCase::create(new TestDomainTag('1dcdf55b-f518-419f-abad-768afa56e6bb'), 'ded58226-d3bf-4a7e-a9eb-cc7d7b7603ce');
        $useCase->modify();

        $this->repository->save($useCase);

        self::assertEquals([
            new RdbmsEvent(
                'be07b19b-c7ab-429e-a9c3-6b7d942122c0',
                [
                    '1dcdf55b-f518-419f-abad-768afa56e6bb',
                    'ded58226-d3bf-4a7e-a9eb-cc7d7b7603ce',
                ],
                'test.use-case.created',
                'O:69:"Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent":2:{s:2:"id";s:36:"1dcdf55b-f518-419f-abad-768afa56e6bb";s:11:"secondaryId";s:36:"ded58226-d3bf-4a7e-a9eb-cc7d7b7603ce";}',
                [],
                $this->clock->time,
            ),
            new RdbmsEvent(
                'be07b19b-c7ab-429e-a9c3-6b7d942122c0',
                [
                    '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a',
                    'afb200a7-4f94-4d40-87b2-50575a1553c7',
                ],
                'test.use-case.modified',
                'O:70:"Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent":0:{}',
                [],
                $this->clock->time,
            ),
        ], $this->eventStoreRepository->events);
    }
}

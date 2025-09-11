<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\UseCase;

use DateTimeImmutable;
use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\CommandHandler\Attribute\AttributeCommandHandlerResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\DefaultUseCaseResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\EventSubscriber\Attribute\AttributeEventSubscriberResolver;
use Gember\EventSourcing\UseCase\UseCaseAttributeRegistry;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\Metadata;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestDomainTag;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EventSourcedUseCaseBehaviorTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        UseCaseAttributeRegistry::initialize(
            new DefaultUseCaseResolver(
                new AttributeDomainTagResolver($attributeResolver = new ReflectorAttributeResolver()),
                new AttributeCommandHandlerResolver($attributeResolver),
                new AttributeEventSubscriberResolver($attributeResolver),
            ),
        );
    }

    #[Test]
    public function itShouldApplyEvents(): void
    {
        $useCase = TestUseCase::create(
            new TestDomainTag('f04ae2c1-eb02-418d-868a-03f1aac16d27'),
            '2da4dd9d-7235-423b-8803-71c0099bc97e',
        );

        self::assertEquals([
            new TestUseCaseCreatedEvent(
                'f04ae2c1-eb02-418d-868a-03f1aac16d27',
                '2da4dd9d-7235-423b-8803-71c0099bc97e',
            ),
        ], $useCase->getAppliedEvents());

        $useCase->modify();

        self::assertEquals([
            new TestUseCaseModifiedEvent(),
        ], $useCase->getAppliedEvents());

        // Should be cleared with previous ^ getAppliedEvents()
        self::assertEquals([], $useCase->getAppliedEvents());
    }

    #[Test]
    public function itShouldGetDomainTags(): void
    {
        $useCase = TestUseCase::create(
            new TestDomainTag('59d17ca2-6c62-4361-be72-112a99f6363b'),
            'e10e8f93-d671-4d6d-9ab3-987134a8a8a1',
        );

        self::assertEquals([
            new TestDomainTag('59d17ca2-6c62-4361-be72-112a99f6363b'),
            'e10e8f93-d671-4d6d-9ab3-987134a8a8a1',
        ], $useCase->getDomainTags());
    }

    #[Test]
    public function itShouldHandleApplyMethodInUseCase(): void
    {
        $useCase = TestUseCase::create(
            new TestDomainTag('c154787f-83eb-4b1b-b080-a34f9b03b461'),
            '2da4dd9d-7235-423b-8803-71c0099bc97e',
        );

        self::assertEquals([
            new TestUseCaseCreatedEvent(
                'c154787f-83eb-4b1b-b080-a34f9b03b461',
                '2da4dd9d-7235-423b-8803-71c0099bc97e',
            ),
        ], $useCase->testAppliedEvents);
    }

    #[Test]
    public function itShouldNotHandleApplyMethodInUseCaseIfNotDefined(): void
    {
        $useCase = TestUseCase::create(
            new TestDomainTag('c154787f-83eb-4b1b-b080-a34f9b03b461'),
            '2da4dd9d-7235-423b-8803-71c0099bc97e',
        );
        $useCase->modify();

        self::assertEquals([
            new TestUseCaseCreatedEvent(
                'c154787f-83eb-4b1b-b080-a34f9b03b461',
                '2da4dd9d-7235-423b-8803-71c0099bc97e',
            ),
        ], $useCase->testAppliedEvents);
    }

    #[Test]
    public function itShouldReconstituteFromEvents(): void
    {
        $useCase = TestUseCase::reconstitute(
            new DomainEventEnvelope(
                'cbee5d86-e691-43c1-bd2d-afdd3f49c964',
                [],
                new TestUseCaseCreatedEvent(
                    '5b66b4ef-1ec1-45c3-ba44-dc9b2479c782',
                    '2da4dd9d-7235-423b-8803-71c0099bc97e',
                ),
                new Metadata(),
                new DateTimeImmutable(),
            ),
            new DomainEventEnvelope(
                '57b591f8-83d4-4bf9-ac4e-b460383444c8',
                [],
                new TestUseCaseModifiedEvent(),
                new Metadata(),
                new DateTimeImmutable(),
            ),
        );

        self::assertSame('57b591f8-83d4-4bf9-ac4e-b460383444c8', $useCase->getLastEventId());
        self::assertSame([], $useCase->getAppliedEvents());
        self::assertEquals([
            new TestDomainTag('5b66b4ef-1ec1-45c3-ba44-dc9b2479c782'),
            '2da4dd9d-7235-423b-8803-71c0099bc97e',
        ], $useCase->getDomainTags());
        self::assertEquals([
            new TestUseCaseCreatedEvent(
                '5b66b4ef-1ec1-45c3-ba44-dc9b2479c782',
                '2da4dd9d-7235-423b-8803-71c0099bc97e',
            ),
        ], $useCase->testAppliedEvents);
    }
}

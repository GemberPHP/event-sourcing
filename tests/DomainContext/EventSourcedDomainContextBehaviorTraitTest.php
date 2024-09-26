<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\DomainContext;

use DateTimeImmutable;
use Gember\EventSourcing\DomainContext\DomainContextAttributeRegistry;
use Gember\EventSourcing\DomainContext\DomainEventEnvelope;
use Gember\EventSourcing\DomainContext\Metadata;
use Gember\EventSourcing\Resolver\DomainContext\SubscriberMethodForEvent\Attribute\AttributeSubscriberMethodForEventResolver;
use Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties\Attribute\AttributeDomainIdPropertiesResolver;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContext;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainId;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EventSourcedDomainContextBehaviorTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DomainContextAttributeRegistry::initialize(
            new AttributeDomainIdPropertiesResolver(
                $attributeResolver = new ReflectorAttributeResolver(),
            ),
            new AttributeSubscriberMethodForEventResolver($attributeResolver),
        );
    }

    #[Test]
    public function itShouldApplyEvents(): void
    {
        $domainContext = TestDomainContext::create(
            new TestDomainId('f04ae2c1-eb02-418d-868a-03f1aac16d27'),
            '2da4dd9d-7235-423b-8803-71c0099bc97e',
        );

        self::assertEquals([
            new TestDomainContextCreatedEvent(
                'f04ae2c1-eb02-418d-868a-03f1aac16d27',
                '2da4dd9d-7235-423b-8803-71c0099bc97e',
            ),
        ], $domainContext->getAppliedEvents());

        $domainContext->modify();

        self::assertEquals([
            new TestDomainContextModifiedEvent(),
        ], $domainContext->getAppliedEvents());

        // Should be cleared with previous ^ getAppliedEvents()
        self::assertEquals([], $domainContext->getAppliedEvents());
    }

    #[Test]
    public function itShouldGetDomainIds(): void
    {
        $domainContext = TestDomainContext::create(
            new TestDomainId('59d17ca2-6c62-4361-be72-112a99f6363b'),
            'e10e8f93-d671-4d6d-9ab3-987134a8a8a1',
        );

        self::assertEquals([
            new TestDomainId('59d17ca2-6c62-4361-be72-112a99f6363b'),
            'e10e8f93-d671-4d6d-9ab3-987134a8a8a1',
        ], $domainContext->getDomainIds());
    }

    #[Test]
    public function itShouldHandleApplyMethodInDomainContext(): void
    {
        $domainContext = TestDomainContext::create(
            new TestDomainId('c154787f-83eb-4b1b-b080-a34f9b03b461'),
            '2da4dd9d-7235-423b-8803-71c0099bc97e',
        );

        self::assertEquals([
            new TestDomainContextCreatedEvent(
                'c154787f-83eb-4b1b-b080-a34f9b03b461',
                '2da4dd9d-7235-423b-8803-71c0099bc97e',
            ),
        ], $domainContext->testAppliedEvents);
    }

    #[Test]
    public function itShouldNotHandleApplyMethodInDomainContextIfNotDefined(): void
    {
        $domainContext = TestDomainContext::create(
            new TestDomainId('c154787f-83eb-4b1b-b080-a34f9b03b461'),
            '2da4dd9d-7235-423b-8803-71c0099bc97e',
        );
        $domainContext->modify();

        self::assertEquals([
            new TestDomainContextCreatedEvent(
                'c154787f-83eb-4b1b-b080-a34f9b03b461',
                '2da4dd9d-7235-423b-8803-71c0099bc97e',
            ),
        ], $domainContext->testAppliedEvents);
    }

    #[Test]
    public function itShouldReconstituteFromEvents(): void
    {
        $domainContext = TestDomainContext::reconstitute(
            new DomainEventEnvelope(
                'cbee5d86-e691-43c1-bd2d-afdd3f49c964',
                [],
                new TestDomainContextCreatedEvent(
                    '5b66b4ef-1ec1-45c3-ba44-dc9b2479c782',
                    '2da4dd9d-7235-423b-8803-71c0099bc97e',
                ),
                new Metadata(),
                new DateTimeImmutable(),
            ),
            new DomainEventEnvelope(
                '57b591f8-83d4-4bf9-ac4e-b460383444c8',
                [],
                new TestDomainContextModifiedEvent(),
                new Metadata(),
                new DateTimeImmutable(),
            ),
        );

        self::assertSame('57b591f8-83d4-4bf9-ac4e-b460383444c8', $domainContext->getLastEventId());
        self::assertSame([], $domainContext->getAppliedEvents());
        self::assertEquals([
            new TestDomainId('5b66b4ef-1ec1-45c3-ba44-dc9b2479c782'),
            '2da4dd9d-7235-423b-8803-71c0099bc97e',
        ], $domainContext->getDomainIds());
        self::assertEquals([
            new TestDomainContextCreatedEvent(
                '5b66b4ef-1ec1-45c3-ba44-dc9b2479c782',
                '2da4dd9d-7235-423b-8803-71c0099bc97e',
            ),
        ], $domainContext->testAppliedEvents);
    }
}

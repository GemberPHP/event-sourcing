<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\EventStore;

use DateTimeImmutable;
use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\Interface\InterfaceDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\Stacked\StackedDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\SagaId\Attribute\AttributeSagaIdResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\DefaultDomainEventResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Attribute\AttributeEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\ClassName\ClassNameEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Interface\InterfaceEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Stacked\StackedEventNameResolver;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\Metadata;
use Gember\EventSourcing\EventStore\DomainEventEnvelopeFactory;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\Util\Generator\Identity\TestIdentityGenerator;
use Gember\EventSourcing\Test\TestDoubles\Util\Time\Clock\TestClock;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DomainEventEnvelopeFactoryTest extends TestCase
{
    private TestClock $clock;
    private DomainEventEnvelopeFactory $factory;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DomainEventEnvelopeFactory(
            new DefaultDomainEventResolver(
                new StackedEventNameResolver(
                    [
                        new AttributeEventNameResolver($attributeResolver = new ReflectorAttributeResolver()),
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
                new AttributeSagaIdResolver($attributeResolver),
            ),
            new TestIdentityGenerator(),
            $this->clock = new TestClock(),
        );
    }

    #[Test]
    public function itShouldCreateDomainEventEnvelope(): void
    {
        $this->clock->time = new DateTimeImmutable('2024-10-01 12:30:32');

        $envelope = $this->factory->createFromAppliedEvent($event = new TestUseCaseCreatedEvent(
            '8a9744a2-ce1b-42a0-825e-bf26731d6355',
            '3a084912-dd22-4d93-bf70-b188d7833b90',
        ));

        self::assertEquals(new DomainEventEnvelope(
            'be07b19b-c7ab-429e-a9c3-6b7d942122c0',
            [
                '8a9744a2-ce1b-42a0-825e-bf26731d6355',
                '3a084912-dd22-4d93-bf70-b188d7833b90',
            ],
            $event,
            new Metadata(),
            new DateTimeImmutable('2024-10-01 12:30:32'),
        ), $envelope);

        $envelope = $this->factory->createFromAppliedEvent($event = new TestUseCaseModifiedEvent());

        self::assertEquals(new DomainEventEnvelope(
            'be07b19b-c7ab-429e-a9c3-6b7d942122c0',
            [
                '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a',
                'afb200a7-4f94-4d40-87b2-50575a1553c7',
            ],
            $event,
            new Metadata(),
            new DateTimeImmutable('2024-10-01 12:30:32'),
        ), $envelope);
    }
}

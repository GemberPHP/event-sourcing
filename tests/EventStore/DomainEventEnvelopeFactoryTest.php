<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\EventStore;

use DateTimeImmutable;
use Gember\EventSourcing\DomainContext\DomainEventEnvelope;
use Gember\EventSourcing\DomainContext\Metadata;
use Gember\EventSourcing\EventStore\DomainEventEnvelopeFactory;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\Attribute\AttributeDomainIdsResolver;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\Util\Generator\Identity\TestIdentityGenerator;
use Gember\EventSourcing\Test\TestDoubles\Util\Time\Clock\TestClock;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
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
            new AttributeDomainIdsResolver(new ReflectorAttributeResolver()),
            new TestIdentityGenerator(),
            $this->clock = new TestClock(),
        );
    }

    #[Test]
    public function itShouldCreateDomainEventEnvelope(): void
    {
        $this->clock->time = new DateTimeImmutable('2024-10-01 12:30:32');

        $envelope = $this->factory->createFromAppliedEvent($event = new TestDomainContextCreatedEvent(
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
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\EventStore\Rdbms;

use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\Metadata;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsDomainEventEnvelopeFactory;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsEvent;
use Gember\EventSourcing\Test\TestDoubles\Registry\TestEventRegistry;
use Gember\EventSourcing\Test\TestDoubles\Util\Serialization\Serializer\TestSerializer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use DateTimeImmutable;
use stdClass;

/**
 * @internal
 */
final class RdbmsDomainEventEnvelopeFactoryTest extends TestCase
{
    private RdbmsDomainEventEnvelopeFactory $factory;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new RdbmsDomainEventEnvelopeFactory(
            new TestSerializer(),
            new TestEventRegistry(),
        );
    }

    #[Test]
    public function itShouldCreateDomainEventEnvelope(): void
    {
        $domainEventEnvelope = $this->factory->createFromRdbmsEvent(new RdbmsEvent(
            '715af8a1-f1ef-4b4f-867d-f7d77c4b35a1',
            [
                'c3433de9-4e45-42c7-a362-31ce81cf71af',
            ],
            'event-name',
            '[]',
            ['some' => 'data'],
            $appliedAt = new DateTimeImmutable(),
        ));

        self::assertEquals(new DomainEventEnvelope(
            '715af8a1-f1ef-4b4f-867d-f7d77c4b35a1',
            [
                'c3433de9-4e45-42c7-a362-31ce81cf71af',
            ],
            new stdClass(),
            new Metadata(['some' => 'data']),
            $appliedAt,
        ), $domainEventEnvelope);
    }
}

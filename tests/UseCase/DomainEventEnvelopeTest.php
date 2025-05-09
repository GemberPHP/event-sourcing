<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\UseCase;

use Gember\EventSourcing\UseCase\Metadata;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use PHPUnit\Framework\Attributes\Test;
use DateTimeImmutable;

/**
 * @internal
 */
final class DomainEventEnvelopeTest extends TestCase
{
    #[Test]
    public function itShouldCreateNewEnvelopeWithMetadata(): void
    {
        $envelope = new DomainEventEnvelope(
            '36eed849-1533-4043-b43f-202578994e11',
            ['7de86c98-581e-4ecd-9843-0a89df0937d4'],
            new TestUseCaseCreatedEvent(
                'c3043ce5-9999-432a-917a-addce40c13d0',
                '5c0cfe37-0eb1-4cc0-8905-7d89e32175f5',
            ),
            new Metadata(['key' => 'value', 'key2' => 'value2']),
            new DateTimeImmutable(),
        );

        $envelopeWithMetadata = $envelope->withMetadata(new Metadata(['key3' => 'value3']));

        self::assertSame(['key' => 'value', 'key2' => 'value2'], $envelope->metadata->metadata);
        self::assertSame(['key3' => 'value3'], $envelopeWithMetadata->metadata->metadata);

        self::assertSame($envelope->eventId, $envelopeWithMetadata->eventId);
        self::assertSame($envelope->event, $envelopeWithMetadata->event);
        self::assertSame($envelope->domainIds, $envelopeWithMetadata->domainIds);
        self::assertSame($envelope->appliedAt, $envelopeWithMetadata->appliedAt);
    }
}

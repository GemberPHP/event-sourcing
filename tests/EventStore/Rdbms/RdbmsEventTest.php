<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\EventStore\Rdbms;

use Gember\EventSourcing\EventStore\Rdbms\RdbmsEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

/**
 * @internal
 */
final class RdbmsEventTest extends TestCase
{
    #[Test]
    public function itShouldUpdateDomainIdImmutable(): void
    {
        $event = new RdbmsEvent(
            '924150cd-7f47-4a32-a503-f15bb46c4725',
            [
                '67700171-468c-4622-a181-fade15cb952b',
            ],
            'event-name',
            'payload',
            [],
            new DateTimeImmutable(),
        );

        $eventWithDomainIdsAdded = $event->withDomainId('e523cb09-ea08-4ef2-b734-3a6c9c7a3113');

        self::assertNotEquals($event, $eventWithDomainIdsAdded);

        self::assertSame([
            '67700171-468c-4622-a181-fade15cb952b',
        ], $event->domainIds);
        self::assertSame([
            '67700171-468c-4622-a181-fade15cb952b',
            'e523cb09-ea08-4ef2-b734-3a6c9c7a3113',
        ], $eventWithDomainIdsAdded->domainIds);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\DomainContext;

use Gember\EventSourcing\DomainContext\NamedDomainEvent;
use Gember\EventSourcing\DomainContext\SpecifiedDomainIdsDomainEvent;

final readonly class TestDomainContextModifiedEvent implements NamedDomainEvent, SpecifiedDomainIdsDomainEvent
{
    public static function getName(): string
    {
        return 'test.domain-context.modified';
    }

    public function getDomainIds(): array
    {
        return [
            '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a',
            new TestDomainId('afb200a7-4f94-4d40-87b2-50575a1553c7'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\UseCase\NamedDomainEvent;
use Gember\EventSourcing\UseCase\SpecifiedDomainTagsDomainMessage;

final readonly class TestUseCaseModifiedEvent implements NamedDomainEvent, SpecifiedDomainTagsDomainMessage
{
    public static function getName(): string
    {
        return 'test.use-case.modified';
    }

    public function getDomainTags(): array
    {
        return [
            '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a',
            new TestDomainTag('afb200a7-4f94-4d40-87b2-50575a1553c7'),
        ];
    }
}

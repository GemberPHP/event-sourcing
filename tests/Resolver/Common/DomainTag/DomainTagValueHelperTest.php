<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Common\DomainTag;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagValueHelper;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class DomainTagValueHelperTest extends TestCase
{
    #[Test]
    public function itShouldGetDomainTagValues(): void
    {
        $event = new TestUseCaseCreatedEvent(
            '5863eda1-cd1d-4121-b80f-7ec1b4b56879',
            '2df85799-a4df-4e08-8880-76a37da19ed7',
        );

        $values = DomainTagValueHelper::getDomainTagValues($event, [
            new DomainTagDefinition('id', DomainTagType::Property),
            new DomainTagDefinition('secondaryId', DomainTagType::Property),
        ]);

        self::assertSame([
            '5863eda1-cd1d-4121-b80f-7ec1b4b56879',
            '2df85799-a4df-4e08-8880-76a37da19ed7',
        ], $values);

        $event = new TestUseCaseModifiedEvent();

        $values = DomainTagValueHelper::getDomainTagValues($event, [
            new DomainTagDefinition('getDomainTags', DomainTagType::Method),
        ]);

        self::assertSame([
            '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a',
            'afb200a7-4f94-4d40-87b2-50575a1553c7',
        ], $values);
    }
}

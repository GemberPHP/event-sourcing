<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventDefinition;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class DomainEventDefinitionTest extends TestCase
{
    #[Test]
    public function itShouldSerializeAndDeserialize(): void
    {
        $definition = new DomainEventDefinition(
            TestUseCaseCreatedEvent::class,
            'test.use-case.created',
            [
                new DomainTagDefinition('id', DomainTagType::Property),
                new DomainTagDefinition('secondaryId', DomainTagType::Property),
            ],
        );

        $serialized = $definition->toPayload();

        self::assertSame([
            'eventClassName' => TestUseCaseCreatedEvent::class,
            'eventName' => 'test.use-case.created',
            'domainTags' => [
                [
                    'domainTagName' => 'id',
                    'type' => 'property',
                ],
                [
                    'domainTagName' => 'secondaryId',
                    'type' => 'property',
                ],
            ],
        ], $serialized);

        $deserialized = DomainEventDefinition::fromPayload($serialized);

        self::assertEquals($deserialized, $definition);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase;

use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\UseCase\EventSubscriberDefinition;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class EventSubscriberDefinitionTest extends TestCase
{
    #[Test]
    public function itShouldSerializeAndDeserialize(): void
    {
        $definition = new EventSubscriberDefinition(
            TestUseCaseCreatedEvent::class,
            '__invoke',
        );

        $serialized = $definition->toPayload();

        self::assertSame([
            'eventClassName' => TestUseCaseCreatedEvent::class,
            'methodName' => '__invoke',
        ], $serialized);

        $deserialized = EventSubscriberDefinition::fromPayload($serialized);

        self::assertEquals($deserialized, $definition);
    }
}

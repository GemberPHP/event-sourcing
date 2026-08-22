<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase;

use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class SnapshotDefinitionTest extends TestCase
{
    #[Test]
    public function itShouldSerializeToPayload(): void
    {
        $definition = new SnapshotDefinition(
            afterEvents: 100,
            afterSourcingTimeMs: 5000,
            onEvents: [stdClass::class],
        );

        self::assertSame([
            'afterEvents' => 100,
            'afterSourcingTimeMs' => 5000,
            'onEvents' => [stdClass::class],
        ], $definition->toPayload());
    }

    #[Test]
    public function itShouldDeserializeFromPayload(): void
    {
        $payload = [
            'afterEvents' => 100,
            'afterSourcingTimeMs' => 5000,
            'onEvents' => [stdClass::class],
        ];

        $definition = SnapshotDefinition::fromPayload($payload);

        self::assertSame(100, $definition->afterEvents);
        self::assertSame(5000, $definition->afterSourcingTimeMs);
        self::assertSame([stdClass::class], $definition->onEvents);
    }

    #[Test]
    public function itShouldRoundTrip(): void
    {
        $definition = new SnapshotDefinition(
            afterEvents: 50,
            afterSourcingTimeMs: 2000,
            onEvents: [stdClass::class],
        );

        $restored = SnapshotDefinition::fromPayload($definition->toPayload());

        self::assertEquals($definition, $restored);
    }

    #[Test]
    public function itShouldHandleNullValues(): void
    {
        $definition = new SnapshotDefinition();

        self::assertSame([
            'afterEvents' => null,
            'afterSourcingTimeMs' => null,
            'onEvents' => null,
        ], $definition->toPayload());

        $restored = SnapshotDefinition::fromPayload($definition->toPayload());

        self::assertEquals($definition, $restored);
    }
}

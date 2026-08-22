<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase\Default\Snapshot\Attribute;

use Gember\EventSourcing\Resolver\UseCase\Default\Snapshot\Attribute\AttributeSnapshotResolver;
use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;
use Gember\EventSourcing\UseCase\Attribute\Snapshot;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\Time\Duration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use stdClass;

/**
 * @internal
 */
final class AttributeSnapshotResolverTest extends TestCase
{
    private AttributeSnapshotResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeSnapshotResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldReturnNullWhenUseCaseHasNoSnapshotAttribute(): void
    {
        $result = $this->resolver->resolve(TestUseCaseWithoutSnapshot::class);

        self::assertNull($result);
    }

    #[Test]
    public function itShouldReturnSnapshotDefinitionWhenUseCaseHasSnapshotAttribute(): void
    {
        $result = $this->resolver->resolve(TestUseCaseWithSnapshot::class);

        self::assertEquals(
            new SnapshotDefinition(
                afterEvents: 100,
                afterSourcingTimeMs: 5000,
                onEvents: [stdClass::class],
            ),
            $result,
        );
    }
}

final class TestUseCaseWithoutSnapshot {}

#[Snapshot(afterEvents: 100, afterSourcingTime: new Duration(5000), onEvents: [stdClass::class])]
final class TestUseCaseWithSnapshot {}

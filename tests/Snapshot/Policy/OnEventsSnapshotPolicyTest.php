<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Snapshot\Policy;

use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;
use Gember\EventSourcing\Snapshot\Policy\OnEventsSnapshotPolicy;
use Gember\EventSourcing\Snapshot\Policy\SnapshotContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class OnEventsSnapshotPolicyTest extends TestCase
{
    private OnEventsSnapshotPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new OnEventsSnapshotPolicy();
    }

    #[Test]
    public function itShouldReturnFalseWhenOnEventsIsNull(): void
    {
        $definition = new SnapshotDefinition(onEvents: null);
        $context = new SnapshotContext(0.0, 0, 0, [new stdClass()]);

        self::assertFalse($this->policy->shouldSnapshot($definition, $context));
    }

    #[Test]
    public function itShouldReturnFalseWhenNoAppliedEventClassMatches(): void
    {
        $definition = new SnapshotDefinition(onEvents: [TestSnapshotEvent::class]);
        $context = new SnapshotContext(0.0, 0, 0, [new stdClass()]);

        self::assertFalse($this->policy->shouldSnapshot($definition, $context));
    }

    #[Test]
    public function itShouldReturnTrueWhenAnAppliedEventClassMatches(): void
    {
        $definition = new SnapshotDefinition(onEvents: [TestSnapshotEvent::class]);
        $context = new SnapshotContext(0.0, 0, 0, [new TestSnapshotEvent()]);

        self::assertTrue($this->policy->shouldSnapshot($definition, $context));
    }
}

final class TestSnapshotEvent {}

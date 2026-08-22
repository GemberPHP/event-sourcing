<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Snapshot\Policy;

use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;
use Gember\EventSourcing\Snapshot\Policy\AfterEventsSnapshotPolicy;
use Gember\EventSourcing\Snapshot\Policy\SnapshotContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AfterEventsSnapshotPolicyTest extends TestCase
{
    private AfterEventsSnapshotPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AfterEventsSnapshotPolicy();
    }

    #[Test]
    public function itShouldReturnFalseWhenAfterEventsIsNull(): void
    {
        $definition = new SnapshotDefinition(afterEvents: null);
        $context = new SnapshotContext(0.0, 10, 0, []);

        self::assertFalse($this->policy->shouldSnapshot($definition, $context));
    }

    #[Test]
    public function itShouldReturnFalseWhenDeltaIsBelowThreshold(): void
    {
        $definition = new SnapshotDefinition(afterEvents: 10);
        $context = new SnapshotContext(0.0, 15, 10, []);

        self::assertFalse($this->policy->shouldSnapshot($definition, $context));
    }

    #[Test]
    public function itShouldReturnTrueWhenDeltaEqualsThreshold(): void
    {
        $definition = new SnapshotDefinition(afterEvents: 10);
        $context = new SnapshotContext(0.0, 20, 10, []);

        self::assertTrue($this->policy->shouldSnapshot($definition, $context));
    }

    #[Test]
    public function itShouldReturnTrueWhenDeltaExceedsThreshold(): void
    {
        $definition = new SnapshotDefinition(afterEvents: 10);
        $context = new SnapshotContext(0.0, 25, 10, []);

        self::assertTrue($this->policy->shouldSnapshot($definition, $context));
    }
}

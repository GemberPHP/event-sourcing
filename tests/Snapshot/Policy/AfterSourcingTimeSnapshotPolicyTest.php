<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Snapshot\Policy;

use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;
use Gember\EventSourcing\Snapshot\Policy\AfterSourcingTimeSnapshotPolicy;
use Gember\EventSourcing\Snapshot\Policy\SnapshotContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AfterSourcingTimeSnapshotPolicyTest extends TestCase
{
    private AfterSourcingTimeSnapshotPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AfterSourcingTimeSnapshotPolicy();
    }

    #[Test]
    public function itShouldReturnFalseWhenAfterSourcingTimeMsIsNull(): void
    {
        $definition = new SnapshotDefinition(afterSourcingTimeMs: null);
        $context = new SnapshotContext(500.0, 0, 0, []);

        self::assertFalse($this->policy->shouldSnapshot($definition, $context));
    }

    #[Test]
    public function itShouldReturnFalseWhenSourcingTimeIsBelowThreshold(): void
    {
        $definition = new SnapshotDefinition(afterSourcingTimeMs: 1000);
        $context = new SnapshotContext(500.0, 0, 0, []);

        self::assertFalse($this->policy->shouldSnapshot($definition, $context));
    }

    #[Test]
    public function itShouldReturnTrueWhenSourcingTimeEqualsThreshold(): void
    {
        $definition = new SnapshotDefinition(afterSourcingTimeMs: 1000);
        $context = new SnapshotContext(1000.0, 0, 0, []);

        self::assertTrue($this->policy->shouldSnapshot($definition, $context));
    }

    #[Test]
    public function itShouldReturnTrueWhenSourcingTimeExceedsThreshold(): void
    {
        $definition = new SnapshotDefinition(afterSourcingTimeMs: 1000);
        $context = new SnapshotContext(1500.0, 0, 0, []);

        self::assertTrue($this->policy->shouldSnapshot($definition, $context));
    }
}

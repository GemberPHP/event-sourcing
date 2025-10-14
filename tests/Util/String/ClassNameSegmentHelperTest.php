<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\String;

use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Util\String\ClassNameSegmentHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

/**
 * @internal
 */
final class ClassNameSegmentHelperTest extends TestCase
{
    #[Test]
    public function itShouldGetLastSegmentOfFqcn(): void
    {
        $name = ClassNameSegmentHelper::getLastSegment(TestUseCaseCreatedEvent::class);

        self::assertSame('TestUseCaseCreatedEvent', $name);
    }

    #[Test]
    public function itShouldGetLastSegmentOfFqcnOfOneSegment(): void
    {
        $name = ClassNameSegmentHelper::getLastSegment(DateTimeImmutable::class);

        self::assertSame('DateTimeImmutable', $name);
    }
}

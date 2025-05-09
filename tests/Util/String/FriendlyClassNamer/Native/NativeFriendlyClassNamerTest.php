<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\String\FriendlyClassNamer\Native;

use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class NativeFriendlyClassNamerTest extends TestCase
{
    #[Test]
    public function itShouldCreateFriendlyName(): void
    {
        $namer = new NativeFriendlyClassNamer(new NativeInflector());
        $friendlyName = $namer->createFriendlyClassName(TestUseCase::class);

        self::assertSame(
            'gember.event-sourcing.test.test-doubles.use-case.test-use-case',
            $friendlyName,
        );
    }
}

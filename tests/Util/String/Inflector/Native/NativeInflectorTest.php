<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\String\Inflector\Native;

use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class NativeInflectorTest extends TestCase
{
    #[Test]
    public function itShouldConvertToSnakeCase(): void
    {
        $inflector = new NativeInflector();

        self::assertSame(
            'gember\event_sourcing\util\string\inflector\native\native_inflector',
            $inflector->toSnakeCase('Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector'),
        );
    }
}

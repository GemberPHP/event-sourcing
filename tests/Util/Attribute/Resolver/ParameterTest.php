<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Attribute\Resolver;

use Gember\EventSourcing\Util\Attribute\Resolver\Parameter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class ParameterTest extends TestCase
{
    #[Test]
    public function itShouldCreateFromArray(): void
    {
        $parameter = Parameter::fromArray([
            'name' => 'parameter',
            'type' => stdClass::class,
        ]);

        self::assertSame('parameter', $parameter->name);
        self::assertSame(stdClass::class, $parameter->type);

        $parameter = Parameter::fromArray([
            'name' => 'parameter',
            'type' => null,
        ]);

        self::assertSame('parameter', $parameter->name);
        self::assertNull($parameter->type);
    }

    #[Test]
    public function itShouldSerialize(): void
    {
        self::assertSame(
            '{"name":"parameter","type":"stdClass"}',
            json_encode(new Parameter('parameter', stdClass::class)),
        );

        self::assertSame(
            '{"name":"parameter","type":null}',
            json_encode(new Parameter('parameter', null)),
        );
    }
}

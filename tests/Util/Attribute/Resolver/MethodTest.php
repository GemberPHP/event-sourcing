<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Attribute\Resolver;

use Gember\EventSourcing\Util\Attribute\Resolver\Method;
use Gember\EventSourcing\Util\Attribute\Resolver\Parameter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class MethodTest extends TestCase
{
    #[Test]
    public function itShouldCreateFromArray(): void
    {
        $method = Method::fromArray([
            'name' => 'aMethod',
            'parameters' => [
                [
                    'name' => 'parameter',
                    'type' => stdClass::class,
                ],
                [
                    'name' => 'parameter2',
                    'type' => null,
                ],
            ],
        ]);

        self::assertSame('aMethod', $method->name);
        self::assertEquals([
            new Parameter('parameter', stdClass::class),
            new Parameter('parameter2', null),
        ], $method->parameters);
    }

    #[Test]
    public function itShouldSerialize(): void
    {
        self::assertSame(
            '{"name":"aMethod","parameters":[{"name":"parameter","type":"stdClass"},{"name":"parameter","type":null}]}',
            json_encode(new Method('aMethod', [
                new Parameter('parameter', stdClass::class),
                new Parameter('parameter', null),
            ])),
        );
    }
}

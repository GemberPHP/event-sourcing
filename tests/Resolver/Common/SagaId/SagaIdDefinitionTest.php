<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Common\SagaId;

use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdDefinition;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class SagaIdDefinitionTest extends TestCase
{
    #[Test]
    public function itShouldCreateDefinitionFromPayload(): void
    {
        $definition = SagaIdDefinition::fromPayload([
            'sagaIdName' => 'some.name',
        ]);

        self::assertSame('some.name', $definition->sagaIdName);
    }

    #[Test]
    public function itShouldSerizalizeDefinitionToPayload(): void
    {
        $definition = new SagaIdDefinition('some.name');

        self::assertSame([
            'sagaIdName' => 'some.name',
        ], $definition->toPayload());
    }
}

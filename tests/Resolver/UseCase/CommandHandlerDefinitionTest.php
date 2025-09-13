<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase;

use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Common\CreationPolicy;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class CommandHandlerDefinitionTest extends TestCase
{
    #[Test]
    public function itShouldSerializeAndDeserialize(): void
    {
        $definition = new CommandHandlerDefinition(
            TestUseCaseWithCommand::class,
            '__invoke',
            CreationPolicy::Never,
        );

        $serialized = $definition->toPayload();

        self::assertSame([
            'commandClassName' => TestUseCaseWithCommand::class,
            'methodName' => '__invoke',
            'policy' => 'never',
        ], $serialized);

        $deserialized = CommandHandlerDefinition::fromPayload($serialized);

        self::assertEquals($deserialized, $definition);
    }
}

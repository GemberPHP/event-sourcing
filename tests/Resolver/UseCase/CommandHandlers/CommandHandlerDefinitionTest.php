<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase\CommandHandlers;

use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommandHandler;
use Gember\EventSourcing\UseCase\Attribute\CreationPolicy;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\CommandHandlerDefinition;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class CommandHandlerDefinitionTest extends TestCase
{
    #[Test]
    public function itShouldSerializeToPayload(): void
    {
        $payload = (new CommandHandlerDefinition(
            TestUseCaseWithCommand::class,
            TestUseCaseWithCommandHandler::class,
            '__invoke',
            CreationPolicy::Never,
        ))->toPayload();

        self::assertSame([
            'commandName' => TestUseCaseWithCommand::class,
            'useCaseClassName' => TestUseCaseWithCommandHandler::class,
            'methodName' => '__invoke',
            'policy' => CreationPolicy::Never->value,
        ], $payload);
    }

    #[Test]
    public function itShouldDeserializeFromPayload(): void
    {
        $definition = CommandHandlerDefinition::fromPayload([
            'commandName' => TestUseCaseWithCommand::class,
            'useCaseClassName' => TestUseCaseWithCommandHandler::class,
            'methodName' => '__invoke',
            'policy' => CreationPolicy::Never->value,
        ]);

        self::assertEquals(new CommandHandlerDefinition(
            TestUseCaseWithCommand::class,
            TestUseCaseWithCommandHandler::class,
            '__invoke',
            CreationPolicy::Never,
        ), $definition);
    }
}

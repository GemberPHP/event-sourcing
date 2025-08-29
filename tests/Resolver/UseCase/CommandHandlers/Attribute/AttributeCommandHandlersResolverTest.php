<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase\CommandHandlers\Attribute;

use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\Attribute\AttributeCommandHandlersResolver;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\CommandHandlerDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestSecondUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommandHandler;
use Gember\EventSourcing\UseCase\Attribute\CreationPolicy;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class AttributeCommandHandlersResolverTest extends TestCase
{
    private AttributeCommandHandlersResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeCommandHandlersResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldResolveAllCommandHandlersInUseCase(): void
    {
        $definitions = $this->resolver->resolve(TestUseCaseWithCommandHandler::class);

        self::assertEquals([
            new CommandHandlerDefinition(
                TestUseCaseWithCommand::class,
                TestUseCaseWithCommandHandler::class,
                '__invoke',
                CreationPolicy::Never,
            ),
            new CommandHandlerDefinition(
                TestSecondUseCaseWithCommand::class,
                TestUseCaseWithCommandHandler::class,
                'second',
                CreationPolicy::IfMissing,
            ),
        ], $definitions);
    }
}

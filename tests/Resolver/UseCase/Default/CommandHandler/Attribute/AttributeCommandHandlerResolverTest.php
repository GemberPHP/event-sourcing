<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase\Default\CommandHandler\Attribute;

use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestSecondUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommandHandler;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\UseCase\Default\CommandHandler\Attribute\AttributeCommandHandlerResolver;
use Override;

/**
 * @internal
 */
final class AttributeCommandHandlerResolverTest extends TestCase
{
    private AttributeCommandHandlerResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeCommandHandlerResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldResolveCommandHandlers(): void
    {
        $definition = $this->resolver->resolve(TestUseCaseWithCommandHandler::class);

        self::assertEquals([
            new CommandHandlerDefinition(
                TestUseCaseWithCommand::class,
                '__invoke',
                CreationPolicy::Never,
            ),
            new CommandHandlerDefinition(
                TestSecondUseCaseWithCommand::class,
                'second',
                CreationPolicy::IfMissing,
            ),
        ], $definition);
    }
}

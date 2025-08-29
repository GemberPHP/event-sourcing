<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Registry\CommandHandler\Reflector;

use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerNotRegisteredException;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\Attribute\AttributeCommandHandlersResolver;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\CommandHandlerDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommandHandler;
use Gember\EventSourcing\Test\TestDoubles\Util\File\Finder\TestFinder;
use Gember\EventSourcing\Test\TestDoubles\Util\File\Reflector\TestReflector;
use Gember\EventSourcing\UseCase\Attribute\CreationPolicy;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Registry\CommandHandler\Reflector\ReflectorCommandHandlerRegistry;
use Override;
use stdClass;

/**
 * @internal
 */
final class ReflectorCommandHandlerRegistryTest extends TestCase
{
    private TestFinder $finder;
    private TestReflector $reflector;
    private ReflectorCommandHandlerRegistry $registry;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new ReflectorCommandHandlerRegistry(
            $this->finder = new TestFinder(),
            $this->reflector = new TestReflector(),
            new AttributeCommandHandlersResolver(new ReflectorAttributeResolver()),
            'path',
        );
    }

    #[Test]
    public function itShouldThrowExceptionIfCommandHandlerIsNotRegistered(): void
    {
        self::expectException(CommandHandlerNotRegisteredException::class);

        $this->registry->retrieve(stdClass::class);
    }

    #[Test]
    public function itShouldRetrieveCommandHandlerDefinitionForCommand(): void
    {
        $this->finder->files = [
            'path/to/use-case.php',
            '',
        ];

        $this->reflector->files = [
            'path/to/use-case.php' => TestUseCaseWithCommandHandler::class,
        ];

        $definition = $this->registry->retrieve(TestUseCaseWithCommand::class);

        self::assertEquals(new CommandHandlerDefinition(
            TestUseCaseWithCommand::class,
            TestUseCaseWithCommandHandler::class,
            '__invoke',
            CreationPolicy::Never,
        ), $definition);
    }
}

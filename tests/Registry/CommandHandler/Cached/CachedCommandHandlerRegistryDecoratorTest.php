<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Registry\CommandHandler\Cached;

use Gember\EventSourcing\Registry\CommandHandler\Cached\CachedCommandHandlerRegistryDecorator;
use Gember\EventSourcing\Registry\CommandHandler\Reflector\ReflectorCommandHandlerRegistry;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\Attribute\AttributeCommandHandlersResolver;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\CommandHandlerDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommandHandler;
use Gember\EventSourcing\Test\TestDoubles\Util\Cache\TestCache;
use Gember\EventSourcing\Test\TestDoubles\Util\File\Finder\TestFinder;
use Gember\EventSourcing\Test\TestDoubles\Util\File\Reflector\TestReflector;
use Gember\EventSourcing\UseCase\Attribute\CreationPolicy;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Override;

/**
 * @internal
 */
final class CachedCommandHandlerRegistryDecoratorTest extends TestCase
{
    private TestCache $cache;
    private CachedCommandHandlerRegistryDecorator $registry;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $finder = new TestFinder();
        $finder->files = [
            'path/to/use-case.php',
            '',
        ];

        $reflector = new TestReflector();
        $reflector->files = [
            'path/to/use-case.php' => TestUseCaseWithCommandHandler::class,
        ];

        $this->registry = new CachedCommandHandlerRegistryDecorator(
            new ReflectorCommandHandlerRegistry(
                $finder,
                $reflector,
                new AttributeCommandHandlersResolver(new ReflectorAttributeResolver()),
                'path',
            ),
            $this->cache = new TestCache(),
            new NativeFriendlyClassNamer(new NativeInflector()),
        );
    }

    #[Test]
    public function itShouldResolveFromCache(): void
    {
        $this->cache->set(
            'gember.registry.command_handler.gember.event-sourcing.test.test-doubles.use-case.test-use-case-with-command',
            '{"commandName":"Gember\\\EventSourcing\\\Test\\\TestDoubles\\\UseCase\\\TestUseCaseWithCommand","useCaseClassName":"Gember\\\EventSourcing\\\Test\\\TestDoubles\\\UseCase\\\TestUseCaseWithCommandHandler","methodName":"another","policy":"never"}',
        );

        $definition = $this->registry->retrieve(TestUseCaseWithCommand::class);

        self::assertEquals(new CommandHandlerDefinition(
            TestUseCaseWithCommand::class,
            TestUseCaseWithCommandHandler::class,
            'another',
            CreationPolicy::Never,
        ), $definition);
    }

    #[Test]
    public function itShouldStoreInCache(): void
    {
        $definition = $this->registry->retrieve(TestUseCaseWithCommand::class);

        self::assertEquals(new CommandHandlerDefinition(
            TestUseCaseWithCommand::class,
            TestUseCaseWithCommandHandler::class,
            '__invoke',
            CreationPolicy::Never,
        ), $definition);

        self::assertSame(
            '{"commandName":"Gember\\\EventSourcing\\\Test\\\TestDoubles\\\UseCase\\\TestUseCaseWithCommand","useCaseClassName":"Gember\\\EventSourcing\\\Test\\\TestDoubles\\\UseCase\\\TestUseCaseWithCommandHandler","methodName":"__invoke","policy":"never"}',
            $this->cache->get('gember.registry.command_handler.gember.event-sourcing.test.test-doubles.use-case.test-use-case-with-command'),
        );
    }
}

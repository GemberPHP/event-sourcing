<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Registry\CommandHandler\Cached;

use Gember\EventSourcing\Registry\CommandHandler\Cached\CachedCommandHandlerRegistryDecorator;
use Gember\EventSourcing\Registry\CommandHandler\Reflector\ReflectorCommandHandlerRegistry;
use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\Resolver\UseCase\Default\CommandHandler\Attribute\AttributeCommandHandlerResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\DefaultUseCaseResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\EventSubscriber\Attribute\AttributeEventSubscriberResolver;
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
                new DefaultUseCaseResolver(
                    new AttributeDomainTagResolver($attributeResolver = new ReflectorAttributeResolver()),
                    new AttributeCommandHandlerResolver($attributeResolver),
                    new AttributeEventSubscriberResolver($attributeResolver),
                ),
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
            '{"useCaseClassName":"Gember\\\EventSourcing\\\Test\\\TestDoubles\\\UseCase\\\TestUseCaseWithCommandHandler","commandHandlerDefinition":{"commandClassName":"Gember\\\EventSourcing\\\Test\\\TestDoubles\\\UseCase\\\TestUseCaseWithCommand","methodName":"another","policy":"never"}}',
        );

        [$useCaseClassName, $definition] = $this->registry->retrieve(TestUseCaseWithCommand::class);

        self::assertSame(TestUseCaseWithCommandHandler::class, $useCaseClassName);

        self::assertEquals(new CommandHandlerDefinition(
            TestUseCaseWithCommand::class,
            'another',
            CreationPolicy::Never,
        ), $definition);
    }

    #[Test]
    public function itShouldStoreInCache(): void
    {
        [$useCaseClassName, $definition] = $this->registry->retrieve(TestUseCaseWithCommand::class);

        self::assertSame(TestUseCaseWithCommandHandler::class, $useCaseClassName);

        self::assertEquals(new CommandHandlerDefinition(
            TestUseCaseWithCommand::class,
            '__invoke',
            CreationPolicy::Never,
        ), $definition);

        self::assertSame(
            '{"useCaseClassName":"Gember\\\EventSourcing\\\Test\\\TestDoubles\\\UseCase\\\TestUseCaseWithCommandHandler","commandHandlerDefinition":{"commandClassName":"Gember\\\EventSourcing\\\Test\\\TestDoubles\\\UseCase\\\TestUseCaseWithCommand","methodName":"__invoke","policy":"never"}}',
            $this->cache->get('gember.registry.command_handler.gember.event-sourcing.test.test-doubles.use-case.test-use-case-with-command'),
        );
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase\Cached;

use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\Resolver\UseCase\Default\CommandHandler\Attribute\AttributeCommandHandlerResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\DefaultUseCaseResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\EventSubscriber\Attribute\AttributeEventSubscriberResolver;
use Gember\EventSourcing\Resolver\UseCase\EventSubscriberDefinition;
use Gember\EventSourcing\Resolver\UseCase\UseCaseDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestSecondUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\Util\Cache\TestCache;
use Gember\EventSourcing\UseCase\Attribute\CreationPolicy;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\UseCase\Cached\CachedUseCaseResolverDecorator;
use PHPUnit\Framework\Attributes\Test;
use Override;
use stdClass;

/**
 * @internal
 */
final class CachedUseCaseResolverDecoratorTest extends TestCase
{
    private TestCache $cache;
    private CachedUseCaseResolverDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->decorator = new CachedUseCaseResolverDecorator(
            new DefaultUseCaseResolver(
                new AttributeDomainTagResolver($attributeResolver = new ReflectorAttributeResolver()),
                new AttributeCommandHandlerResolver($attributeResolver),
                new AttributeEventSubscriberResolver($attributeResolver),
            ),
            $this->cache = new TestCache(),
            new NativeFriendlyClassNamer(new NativeInflector()),
        );
    }

    #[Test]
    public function itShouldResolveFromCache(): void
    {
        $expectedDefinition = new UseCaseDefinition(
            TestUseCase::class,
            [
                new DomainTagDefinition('id', DomainTagType::Property),
                new DomainTagDefinition('anotherId', DomainTagType::Property),
            ],
            [
                new CommandHandlerDefinition(
                    TestUseCaseWithCommand::class,
                    '__invoke',
                    CreationPolicy::Never,
                ),
                new CommandHandlerDefinition(
                    TestSecondUseCaseWithCommand::class,
                    'method',
                    CreationPolicy::IfMissing,
                ),
            ],
            [
                new EventSubscriberDefinition(TestUseCaseCreatedEvent::class, '__invoke'),
                new EventSubscriberDefinition(TestUseCaseModifiedEvent::class, 'execute'),
            ],
        );

        $this->cache->set('gember.resolver.use_case.std-class', json_encode($expectedDefinition->toPayload()));

        $definition = $this->decorator->resolve(stdClass::class);

        self::assertEquals($expectedDefinition, $definition);
    }

    #[Test]
    public function itShouldStoreInCache(): void
    {
        $definition = $this->decorator->resolve(TestUseCase::class);

        $cachedDefinition = $this->cache->get('gember.resolver.use_case.gember.event-sourcing.test.test-doubles.use-case.test-use-case');

        self::assertEquals(UseCaseDefinition::fromPayload(json_decode($cachedDefinition, true)), $definition);
    }
}

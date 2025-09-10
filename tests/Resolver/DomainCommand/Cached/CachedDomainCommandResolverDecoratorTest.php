<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainCommand\Cached;

use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\DomainCommand\Cached\CachedDomainCommandResolverDecorator;
use Gember\EventSourcing\Resolver\DomainCommand\Default\DefaultDomainCommandResolver;
use Gember\EventSourcing\Resolver\DomainCommand\DomainCommandDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\Util\Cache\TestCache;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Override;
use stdClass;

/**
 * @internal
 */
final class CachedDomainCommandResolverDecoratorTest extends TestCase
{
    private TestCache $cache;
    private CachedDomainCommandResolverDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->decorator = new CachedDomainCommandResolverDecorator(
            new DefaultDomainCommandResolver(
                new AttributeDomainTagResolver(new ReflectorAttributeResolver()),
            ),
            $this->cache = new TestCache(),
            new NativeFriendlyClassNamer(new NativeInflector()),
        );
    }

    #[Test]
    public function itShouldResolveFromCache(): void
    {
        $expectedDefinition = new DomainCommandDefinition(
            TestUseCaseWithCommand::class,
            [
                new DomainTagDefinition('id', DomainTagType::Property),
                new DomainTagDefinition('secondaryId', DomainTagType::Property),
            ],
        );

        $this->cache->set('gember.resolver.domain_command.std-class', json_encode($expectedDefinition->toPayload()));

        $definition = $this->decorator->resolve(stdClass::class);

        self::assertEquals($expectedDefinition, $definition);
    }

    #[Test]
    public function itShouldStoreInCache(): void
    {
        $definition = $this->decorator->resolve(TestUseCaseWithCommand::class);

        $cachedDefinition = $this->cache->get('gember.resolver.domain_command.gember.event-sourcing.test.test-doubles.use-case.test-use-case-with-command');

        self::assertEquals(DomainCommandDefinition::fromPayload(json_decode($cachedDefinition, true)), $definition);
    }
}

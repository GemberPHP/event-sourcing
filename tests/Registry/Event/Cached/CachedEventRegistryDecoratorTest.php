<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Registry\Event\Cached;

use Gember\EventSourcing\Registry\Event\Cached\CachedEventRegistryDecorator;
use Gember\EventSourcing\Registry\Event\Reflector\ReflectorEventRegistry;
use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\DefaultDomainEventResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Attribute\AttributeEventNameResolver;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\Util\Cache\TestCache;
use Gember\EventSourcing\Test\TestDoubles\Util\File\Finder\TestFinder;
use Gember\EventSourcing\Test\TestDoubles\Util\File\Reflector\TestReflector;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class CachedEventRegistryDecoratorTest extends TestCase
{
    private TestCache $cache;
    private CachedEventRegistryDecorator $registry;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $finder = new TestFinder();
        $finder->files = [
            'path/to/event.php',
            '',
        ];

        $reflector = new TestReflector();
        $reflector->files = [
            'path/to/event.php' => TestUseCaseCreatedEvent::class,
        ];

        $this->registry = new CachedEventRegistryDecorator(
            new ReflectorEventRegistry(
                $finder,
                $reflector,
                new DefaultDomainEventResolver(
                    new AttributeEventNameResolver($attributeResolver = new ReflectorAttributeResolver()),
                    new AttributeDomainTagResolver($attributeResolver),
                ),
                'path',
            ),
            $this->cache = new TestCache(),
        );
    }

    #[Test]
    public function itShouldResolveFromCache(): void
    {
        $this->cache->set('gember.registry.event.test.use-case.created', TestUseCaseModifiedEvent::class);

        $eventFqcn = $this->registry->retrieve('test.use-case.created');

        self::assertSame(TestUseCaseModifiedEvent::class, $eventFqcn);
    }

    #[Test]
    public function itShouldStoreInCache(): void
    {
        $eventFqcn = $this->registry->retrieve('test.use-case.created');

        self::assertSame(TestUseCaseCreatedEvent::class, $eventFqcn);
        self::assertSame(TestUseCaseCreatedEvent::class, $this->cache->get('gember.registry.event.test.use-case.created'));
    }
}

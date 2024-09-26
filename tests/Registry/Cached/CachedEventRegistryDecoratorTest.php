<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Registry\Cached;

use Gember\EventSourcing\Registry\Cached\CachedEventRegistryDecorator;
use Gember\EventSourcing\Registry\Reflector\ReflectorEventRegistry;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Attribute\AttributeNormalizedEventNameResolver;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextModifiedEvent;
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
    /**
     * @var TestCache<class-string>
     */
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
            'path/to/event.php' => TestDomainContextCreatedEvent::class,
        ];

        $this->registry = new CachedEventRegistryDecorator(
            new ReflectorEventRegistry(
                $finder,
                $reflector,
                new AttributeNormalizedEventNameResolver(new ReflectorAttributeResolver()),
                'path',
            ),
            $this->cache = new TestCache(),
        );
    }

    #[Test]
    public function itShouldResolveFromCache(): void
    {
        $this->cache->set('gember.event-registry.test.domain-context.created', TestDomainContextModifiedEvent::class);

        $eventFqcn = $this->registry->retrieve('test.domain-context.created');

        self::assertSame(TestDomainContextModifiedEvent::class, $eventFqcn);
    }

    #[Test]
    public function itShouldStoreInCache(): void
    {
        $eventFqcn = $this->registry->retrieve('test.domain-context.created');

        self::assertSame(TestDomainContextCreatedEvent::class, $eventFqcn);
        self::assertSame(TestDomainContextCreatedEvent::class, $this->cache->get('gember.event-registry.test.domain-context.created'));
    }
}

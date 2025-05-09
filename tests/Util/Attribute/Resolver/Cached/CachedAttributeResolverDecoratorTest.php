<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Attribute\Resolver\Cached;

use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\UseCase\Attribute\DomainId;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\Util\Cache\TestCache;
use Gember\EventSourcing\Util\Attribute\Resolver\Cached\CachedAttributeResolverDecorator;
use Gember\EventSourcing\Util\Attribute\Resolver\Method;
use Gember\EventSourcing\Util\Attribute\Resolver\Parameter;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class CachedAttributeResolverDecoratorTest extends TestCase
{
    private TestCache $cache;
    private CachedAttributeResolverDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->decorator = new CachedAttributeResolverDecorator(
            new ReflectorAttributeResolver(),
            new NativeFriendlyClassNamer(new NativeInflector()),
            $this->cache = new TestCache(),
        );
    }

    #[Test]
    public function itShouldGetPropertyNamesWithAttributeFromCache(): void
    {
        $this->cache->set(
            'gember.attribute-resolver.properties.gember.event-sourcing.test.test-doubles.use-case.test-use-case.gember.event-sourcing.use-case.attribute.domain-id',
            '["domainId","someOtherId"]',
        );

        $names = $this->decorator->getPropertyNamesWithAttribute(
            TestUseCase::class,
            DomainId::class,
        );

        self::assertSame([
            'domainId',
            'someOtherId',
        ], $names);
    }

    #[Test]
    public function itShouldGetPropertyNamesWithAttributeFromDecoratedResolverAndStoreInCache(): void
    {
        $names = $this->decorator->getPropertyNamesWithAttribute(
            TestUseCase::class,
            DomainId::class,
        );

        self::assertSame([
            'domainId',
            'secondaryId',
        ], $names);

        self::assertSame(
            '["domainId","secondaryId"]',
            $this->cache->get('gember.attribute-resolver.properties.gember.event-sourcing.test.test-doubles.use-case.test-use-case.gember.event-sourcing.use-case.attribute.domain-id'),
        );
    }

    #[Test]
    public function itShouldGetMethodsWithAttributeFromCache(): void
    {
        $this->cache->set(
            'gember.attribute-resolver.methods.gember.event-sourcing.test.test-doubles.use-case.test-use-case.gember.event-sourcing.use-case.attribute.domain-event-subscriber',
            '[{"name":"onSomeMethod","parameters":[{"name":"eventName","type":"Gember\\\EventSourcing\\\Test\\\TestDoubles\\\UseCase\\\TestUseCaseCreatedEvent"}]}]',
        );

        $methods = $this->decorator->getMethodsWithAttribute(
            TestUseCase::class,
            DomainEventSubscriber::class,
        );

        self::assertEquals(
            [
                new Method(
                    'onSomeMethod',
                    [
                        new Parameter('eventName', TestUseCaseCreatedEvent::class),
                    ],
                ),
            ],
            $methods,
        );
    }

    #[Test]
    public function itShouldGetMethodsWithAttributeFromDecoratedResolverAndStoreInCache(): void
    {
        $methods = $this->decorator->getMethodsWithAttribute(
            TestUseCase::class,
            DomainEventSubscriber::class,
        );

        self::assertEquals(
            [
                new Method(
                    'onTestUseCaseCreatedEvent',
                    [
                        new Parameter('event', TestUseCaseCreatedEvent::class),
                    ],
                ),
            ],
            $methods,
        );

        self::assertSame(
            '[{"name":"onTestUseCaseCreatedEvent","parameters":[{"name":"event","type":"Gember\\\EventSourcing\\\Test\\\TestDoubles\\\UseCase\\\TestUseCaseCreatedEvent"}]}]',
            $this->cache->get('gember.attribute-resolver.methods.gember.event-sourcing.test.test-doubles.use-case.test-use-case.gember.event-sourcing.use-case.attribute.domain-event-subscriber'),
        );
    }

    #[Test]
    public function itShouldGetAttributesForClassFromCache(): void
    {
        $this->cache->set(
            'gember.attribute-resolver.class-attributes.gember.event-sourcing.test.test-doubles.use-case.test-use-case-created-event.gember.event-sourcing.use-case.attribute.domain-event',
            '["O:50:\"Gember\\\EventSourcing\\\UseCase\\\Attribute\\\DomainEvent\":1:{s:4:\"name\";s:15:\"some.event.name\";}"]',
        );

        $attributes = $this->decorator->getAttributesForClass(
            TestUseCaseCreatedEvent::class,
            DomainEvent::class,
        );

        self::assertEquals([
            new DomainEvent('some.event.name'),
        ], $attributes);
    }

    #[Test]
    public function itShouldGetAttributesForClassFromDecoratedResolverAndStoreInCache(): void
    {
        $attributes = $this->decorator->getAttributesForClass(
            TestUseCaseCreatedEvent::class,
            DomainEvent::class,
        );

        self::assertEquals([
            new DomainEvent('test.use-case.created'),
        ], $attributes);

        self::assertSame(
            '["O:50:\"Gember\\\EventSourcing\\\UseCase\\\Attribute\\\DomainEvent\":1:{s:4:\"name\";s:21:\"test.use-case.created\";}"]',
            $this->cache->get('gember.attribute-resolver.class-attributes.gember.event-sourcing.test.test-doubles.use-case.test-use-case-created-event.gember.event-sourcing.use-case.attribute.domain-event'),
        );
    }
}

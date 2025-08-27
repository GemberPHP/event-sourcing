<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Attribute\Resolver\Reflector;

use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Method;
use Gember\EventSourcing\Util\Attribute\Resolver\Parameter;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class ReflectorAttributeResolverTest extends TestCase
{
    private ReflectorAttributeResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new ReflectorAttributeResolver();
    }

    #[Test]
    public function itShouldGetPropertiesNamesWithAttribute(): void
    {
        $names = $this->resolver->getPropertyNamesWithAttribute(
            TestUseCase::class,
            DomainTag::class,
        );

        self::assertSame([
            'domainTag',
            'secondaryTag',
        ], $names);
    }

    #[Test]
    public function itShouldGetMethodsWithAttribute(): void
    {
        $methods = $this->resolver->getMethodsWithAttribute(
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
    }

    #[Test]
    public function itShouldGetAttributesForClass(): void
    {
        $attributes = $this->resolver->getAttributesForClass(
            TestUseCaseCreatedEvent::class,
            DomainEvent::class,
        );

        self::assertEquals([
            new DomainEvent('test.use-case.created'),
        ], $attributes);
    }
}

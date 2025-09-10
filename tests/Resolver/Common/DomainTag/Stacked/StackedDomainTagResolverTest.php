<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Common\DomainTag\Stacked;

use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\Common\DomainTag\Interface\InterfaceDomainTagResolver;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\Common\DomainTag\Stacked\StackedDomainTagResolver;
use Override;
use stdClass;

/**
 * @internal
 */
final class StackedDomainTagResolverTest extends TestCase
{
    private StackedDomainTagResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new StackedDomainTagResolver(
            [
                new AttributeDomainTagResolver(new ReflectorAttributeResolver()),
                new InterfaceDomainTagResolver(),
            ],
        );
    }

    #[Test]
    public function itShouldResolve(): void
    {
        $definition = $this->resolver->resolve(TestUseCaseCreatedEvent::class);

        self::assertEquals([
            new DomainTagDefinition('id', DomainTagType::Property),
            new DomainTagDefinition('secondaryId', DomainTagType::Property),
        ], $definition);

        $definition = $this->resolver->resolve(TestUseCaseModifiedEvent::class);

        self::assertEquals([
            new DomainTagDefinition(
                'getDomainTags',
                DomainTagType::Method,
            ),
        ], $definition);
    }

    #[Test]
    public function itShouldReturnEmptyWhenNotResolvable(): void
    {
        $definition = $this->resolver->resolve(stdClass::class);

        self::assertSame([], $definition);
    }
}

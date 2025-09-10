<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Common\DomainTag\Interface;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\Common\DomainTag\UnresolvableDomainTagException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\Common\DomainTag\Interface\InterfaceDomainTagResolver;
use PHPUnit\Framework\Attributes\Test;
use Override;

/**
 * @internal
 */
final class InterfaceDomainTagResolverTest extends TestCase
{
    private InterfaceDomainTagResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new InterfaceDomainTagResolver();
    }

    #[Test]
    public function itShouldResolveInterface(): void
    {
        $definition = $this->resolver->resolve(TestUseCaseModifiedEvent::class);

        self::assertEquals([
            new DomainTagDefinition(
                'getDomainTags',
                DomainTagType::Method,
            ),
        ], $definition);
    }

    #[Test]
    public function itShouldFailWhenNoInterface(): void
    {
        self::expectException(UnresolvableDomainTagException::class);

        $this->resolver->resolve(TestUseCaseCreatedEvent::class);
    }
}

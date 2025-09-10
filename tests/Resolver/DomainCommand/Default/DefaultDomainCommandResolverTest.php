<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainCommand\Default;

use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\Common\DomainTag\Interface\InterfaceDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\Stacked\StackedDomainTagResolver;
use Gember\EventSourcing\Resolver\DomainCommand\DomainCommandDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestCommandWithInterface;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\DomainCommand\Default\DefaultDomainCommandResolver;
use PHPUnit\Framework\Attributes\Test;
use Override;

/**
 * @internal
 */
final class DefaultDomainCommandResolverTest extends TestCase
{
    private DefaultDomainCommandResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new DefaultDomainCommandResolver(
            new StackedDomainTagResolver(
                [
                    new AttributeDomainTagResolver(new ReflectorAttributeResolver()),
                    new InterfaceDomainTagResolver(),
                ],
            ),
        );
    }

    #[Test]
    public function itShouldResolveDomainCommand(): void
    {
        $definition = $this->resolver->resolve(TestUseCaseWithCommand::class);

        self::assertEquals(new DomainCommandDefinition(
            TestUseCaseWithCommand::class,
            [
                new DomainTagDefinition('domainTag', DomainTagType::Property),
            ],
        ), $definition);

        $definition = $this->resolver->resolve(TestCommandWithInterface::class);

        self::assertEquals(new DomainCommandDefinition(
            TestCommandWithInterface::class,
            [
                new DomainTagDefinition('getDomainTags', DomainTagType::Method),
            ],
        ), $definition);
    }
}

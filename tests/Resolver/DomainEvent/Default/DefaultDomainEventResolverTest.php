<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\Default;

use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Attribute\AttributeEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Override;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\DomainEvent\Default\DefaultDomainEventResolver;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class DefaultDomainEventResolverTest extends TestCase
{
    private DefaultDomainEventResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new DefaultDomainEventResolver(
            new AttributeEventNameResolver($attributeResolver = new ReflectorAttributeResolver()),
            new AttributeDomainTagResolver($attributeResolver),
        );
    }

    #[Test]
    public function itShouldResolveDomainEvent(): void
    {
        $definition = $this->resolver->resolve(TestUseCaseCreatedEvent::class);

        self::assertEquals(new DomainEventDefinition(
            TestUseCaseCreatedEvent::class,
            'test.use-case.created',
            [
                new DomainTagDefinition('id', DomainTagType::Property),
                new DomainTagDefinition('secondaryId', DomainTagType::Property),
            ],
        ), $definition);
    }
}

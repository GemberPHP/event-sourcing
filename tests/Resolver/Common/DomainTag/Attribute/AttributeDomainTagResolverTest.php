<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Common\DomainTag\Attribute;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\Common\DomainTag\UnresolvableDomainTagException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use PHPUnit\Framework\Attributes\Test;
use Override;
use stdClass;

/**
 * @internal
 */
final class AttributeDomainTagResolverTest extends TestCase
{
    private AttributeDomainTagResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeDomainTagResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldResolveDomainTags(): void
    {
        $definition = $this->resolver->resolve(TestUseCaseCreatedEvent::class);

        self::assertEquals([
            new DomainTagDefinition('id', DomainTagType::Property),
            new DomainTagDefinition('secondaryId', DomainTagType::Property),
        ], $definition);
    }

    #[Test]
    public function itShouldFailWhenNoAttributesAreSet(): void
    {
        self::expectException(UnresolvableDomainTagException::class);

        $this->resolver->resolve(stdClass::class);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainContext\DomainIdProperties\Attribute;

use Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties\Attribute\AttributeDomainIdPropertiesResolver;
use Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties\UnresolvableDomainIdPropertiesException;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContext;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use stdClass;

/**
 * @internal
 */
final class AttributeDomainIdPropertiesResolverTest extends TestCase
{
    private AttributeDomainIdPropertiesResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeDomainIdPropertiesResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldThrowExceptionWhenNoPropertiesAreFound(): void
    {
        self::expectException(UnresolvableDomainIdPropertiesException::class);

        $this->resolver->resolve(stdClass::class); // @phpstan-ignore-line
    }

    #[Test]
    public function itShouldResolveDomainIdPropertiesFromDomainContext(): void
    {
        $properties = $this->resolver->resolve(TestDomainContext::class);

        self::assertSame([
            'domainId',
            'secondaryId',
        ], $properties);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase\DomainTagProperties\Attribute;

use Gember\EventSourcing\Resolver\UseCase\DomainTagProperties\Attribute\AttributeDomainTagsPropertiesResolver;
use Gember\EventSourcing\Resolver\UseCase\DomainTagProperties\UnresolvableDomainTagPropertiesException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use stdClass;

/**
 * @internal
 */
final class AttributeDomainTagPropertiesResolverTest extends TestCase
{
    private AttributeDomainTagsPropertiesResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeDomainTagsPropertiesResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldThrowExceptionWhenNoPropertiesAreFound(): void
    {
        self::expectException(UnresolvableDomainTagPropertiesException::class);

        $this->resolver->resolve(stdClass::class); // @phpstan-ignore-line
    }

    #[Test]
    public function itShouldResolveDomainTagPropertiesFromUseCase(): void
    {
        $properties = $this->resolver->resolve(TestUseCase::class);

        self::assertSame([
            'domainTag',
            'secondaryTag',
        ], $properties);
    }
}

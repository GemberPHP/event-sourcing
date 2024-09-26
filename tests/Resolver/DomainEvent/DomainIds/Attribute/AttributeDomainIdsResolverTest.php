<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\DomainIds\Attribute;

use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\Attribute\AttributeDomainIdsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\UnresolvableDomainIdsException;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextModifiedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class AttributeDomainIdsResolverTest extends TestCase
{
    private AttributeDomainIdsResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeDomainIdsResolver(new ReflectorAttributeResolver());
    }

    #[Test]
    public function itShouldThrowExceptionWhenDomainIdsCannotBeResolved(): void
    {
        self::expectException(UnresolvableDomainIdsException::class);

        $this->resolver->resolve(new TestDomainContextModifiedEvent());
    }

    #[Test]
    public function itShouldResolveDomainIds(): void
    {
        $domainIds = $this->resolver->resolve(new TestDomainContextCreatedEvent(
            '7cb5c1e5-be4d-4520-90ab-b2300cb67ae1',
            'fb08765f-2549-44b3-8b47-14a0dab158ea',
        ));

        self::assertSame([
            '7cb5c1e5-be4d-4520-90ab-b2300cb67ae1',
            'fb08765f-2549-44b3-8b47-14a0dab158ea',
        ], $domainIds);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\NormalizedEventName\Interface;

use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Interface\InterfaceNormalizedEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\UnresolvableEventNameException;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextModifiedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use stdClass;

/**
 * @internal
 */
final class InterfaceNormalizedEventNameResolverTest extends TestCase
{
    private InterfaceNormalizedEventNameResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new InterfaceNormalizedEventNameResolver();
    }

    #[Test]
    public function itShouldThrowExceptionWhenEventNameCannotBeResolved(): void
    {
        self::expectException(UnresolvableEventNameException::class);

        $this->resolver->resolve(stdClass::class);
    }

    #[Test]
    public function itShouldResolveEventName(): void
    {
        $eventName = $this->resolver->resolve(TestDomainContextModifiedEvent::class);

        self::assertSame('test.domain-context.modified', $eventName);
    }
}

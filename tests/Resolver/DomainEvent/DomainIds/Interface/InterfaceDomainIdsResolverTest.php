<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\DomainIds\Interface;

use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\Interface\InterfaceDomainIdsResolver;
use Gember\EventSourcing\Resolver\DomainEvent\DomainIds\UnresolvableDomainIdsException;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class InterfaceDomainIdsResolverTest extends TestCase
{
    private InterfaceDomainIdsResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new InterfaceDomainIdsResolver();
    }

    #[Test]
    public function itShouldThrowExceptionWhenDomainIdsCannotBeResolved(): void
    {
        self::expectException(UnresolvableDomainIdsException::class);

        $this->resolver->resolve(new TestDomainContextCreatedEvent(
            '7dc468da-5285-4ba0-bba6-fbdd4068d032',
            '8fb156a6-58a6-41de-92dc-f4dc52294581',
        ));
    }

    #[Test]
    public function itShouldResolveDomainIds(): void
    {
        $domainIds = $this->resolver->resolve(new TestDomainContextModifiedEvent());

        self::assertEquals([
            '3faa2ded-b0c4-4d62-a16d-3eb3dcf3ee5a',
            new TestDomainId('afb200a7-4f94-4d40-87b2-50575a1553c7'),
        ], $domainIds);
    }
}

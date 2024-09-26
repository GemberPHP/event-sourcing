<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainContext\DomainIdProperties;

use Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties\UnresolvableDomainIdPropertiesException;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UnresolvableDomainIdPropertiesExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateException(): void
    {
        $exception = UnresolvableDomainIdPropertiesException::create(TestDomainContext::class, 'It failed');

        self::assertSame(
            'Unresolvable domainId properties for context Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContext: It failed',
            $exception->getMessage(),
        );
    }
}

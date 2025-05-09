<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase\DomainIdProperties;

use Gember\EventSourcing\Resolver\UseCase\DomainIdProperties\UnresolvableDomainIdPropertiesException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase;
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
        $exception = UnresolvableDomainIdPropertiesException::create(TestUseCase::class, 'It failed');

        self::assertSame(
            'Unresolvable domainId properties for use case Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase: It failed',
            $exception->getMessage(),
        );
    }
}

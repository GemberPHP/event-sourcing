<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase\DomainTagProperties;

use Gember\EventSourcing\Resolver\UseCase\DomainTagProperties\UnresolvableDomainTagPropertiesException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UnresolvableDomainTagPropertiesExceptionTest extends TestCase
{
    #[Test]
    public function itShouldCreateException(): void
    {
        $exception = UnresolvableDomainTagPropertiesException::create(TestUseCase::class, 'It failed');

        self::assertSame(
            'Unresolvable domainTag properties for use case Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase: It failed',
            $exception->getMessage(),
        );
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\Default\EventName\Interface;

use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Interface\InterfaceEventNameResolver;
use PHPUnit\Framework\Attributes\Test;
use Override;

/**
 * @internal
 */
final class InterfaceEventNameResolverTest extends TestCase
{
    private InterfaceEventNameResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new InterfaceEventNameResolver();
    }

    #[Test]
    public function itShouldResolveEventNameFromInterface(): void
    {
        $eventName = $this->resolver->resolve(TestUseCaseModifiedEvent::class);

        self::assertSame('test.use-case.modified', $eventName);
    }
}

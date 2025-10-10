<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Saga\Default\SagaName\Interface;

use Gember\EventSourcing\Resolver\Saga\Default\SagaName\UnresolvableSagaNameException;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSaga;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaWithNamedInterface;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Interface\InterfaceSagaNameResolver;
use PHPUnit\Framework\Attributes\Test;
use Override;

/**
 * @internal
 */
final class InterfaceSagaNameResolverTest extends TestCase
{
    private InterfaceSagaNameResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new InterfaceSagaNameResolver();
    }

    #[Test]
    public function itShouldResolveSagaNameFromInterface(): void
    {
        $name = $this->resolver->resolve(TestSagaWithNamedInterface::class);

        self::assertSame('saga.test-named', $name);
    }

    #[Test]
    public function itShouldThrowWhenSagaNameIsUnresolvable(): void
    {
        self::expectException(UnresolvableSagaNameException::class);

        $this->resolver->resolve(TestSaga::class);
    }
}

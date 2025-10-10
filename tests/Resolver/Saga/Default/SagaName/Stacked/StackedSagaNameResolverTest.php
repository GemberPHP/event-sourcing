<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Saga\Default\SagaName\Stacked;

use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Attribute\AttributeSagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\ClassName\ClassNameSagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Interface\InterfaceSagaNameResolver;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSaga;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaWithNamedInterface;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaWithoutName;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Stacked\StackedSagaNameResolver;
use Override;

/**
 * @internal
 */
final class StackedSagaNameResolverTest extends TestCase
{
    private StackedSagaNameResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new StackedSagaNameResolver(
            [
                new AttributeSagaNameResolver(new ReflectorAttributeResolver()),
                new InterfaceSagaNameResolver(),
            ],
            new ClassNameSagaNameResolver(new NativeFriendlyClassNamer(new NativeInflector())),
        );
    }

    #[Test]
    public function itShouldResolveAllSagaNames(): void
    {
        self::assertSame('saga.test', $this->resolver->resolve(TestSaga::class));
        self::assertSame('saga.test-named', $this->resolver->resolve(TestSagaWithNamedInterface::class));
        self::assertSame('gember.event-sourcing.test.test-doubles.saga.test-saga-without-name', $this->resolver->resolve(TestSagaWithoutName::class));
    }
}

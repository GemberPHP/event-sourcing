<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Saga\Default\SagaName\ClassName;

use Gember\EventSourcing\Test\TestDoubles\Saga\TestSagaWithoutName;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\ClassName\ClassNameSagaNameResolver;
use PHPUnit\Framework\Attributes\Test;
use Override;

/**
 * @internal
 */
final class ClassNameSagaNameResolverTest extends TestCase
{
    private ClassNameSagaNameResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new ClassNameSagaNameResolver(
            new NativeFriendlyClassNamer(new NativeInflector()),
        );
    }

    #[Test]
    public function itShouldResolveSagaNameFromClassName(): void
    {
        $name = $this->resolver->resolve(TestSagaWithoutName::class);

        self::assertSame('gember.event-sourcing.test.test-doubles.saga.test-saga-without-name', $name);
    }
}

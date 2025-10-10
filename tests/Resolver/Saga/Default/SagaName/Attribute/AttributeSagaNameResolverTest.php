<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Saga\Default\SagaName\Attribute;

use Gember\EventSourcing\Resolver\Saga\Default\SagaName\UnresolvableSagaNameException;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSaga;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\Attribute\AttributeSagaNameResolver;
use PHPUnit\Framework\Attributes\Test;
use Override;
use stdClass;

/**
 * @internal
 */
final class AttributeSagaNameResolverTest extends TestCase
{
    private AttributeSagaNameResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeSagaNameResolver(new ReflectorAttributeResolver());
    }

    #[Test]
    public function itShouldResolveSagaNameFromAttribute(): void
    {
        $name = $this->resolver->resolve(TestSaga::class);

        self::assertSame('saga.test', $name);
    }

    #[Test]
    public function itShouldThrowWhenSagaNameIsUnresolvable(): void
    {
        self::expectException(UnresolvableSagaNameException::class);

        $this->resolver->resolve(stdClass::class);
    }
}

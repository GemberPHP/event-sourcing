<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Common\SagaId\Attribute;

use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdDefinition;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSaga;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\Common\SagaId\Attribute\AttributeSagaIdResolver;
use Override;

/**
 * @internal
 */
final class AttributeSagaIdResolverTest extends TestCase
{
    private AttributeSagaIdResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeSagaIdResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldReturnEmptyListWhenSagaIdAreNotFound(): void
    {
        $definitions = $this->resolver->resolve(TestUseCaseModifiedEvent::class);

        self::assertSame([], $definitions);
    }

    #[Test]
    public function itShouldResolveSagaIdFromClass(): void
    {
        $definitions = $this->resolver->resolve(TestUseCaseCreatedEvent::class);

        self::assertEquals([
            new SagaIdDefinition('id', 'id'),
        ], $definitions);
    }

    #[Test]
    public function itShouldResolveSagaIdFromClassWithCustomName(): void
    {
        $definitions = $this->resolver->resolve(TestSaga::class);

        self::assertEquals([
            new SagaIdDefinition('anotherName', 'someId'),
        ], $definitions);
    }
}

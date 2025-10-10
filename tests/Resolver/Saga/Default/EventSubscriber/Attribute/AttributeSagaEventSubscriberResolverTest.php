<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Saga\Default\EventSubscriber\Attribute;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Resolver\Saga\Default\EventSubscriber\Attribute\AttributeSagaEventSubscriberResolver;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSaga;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use stdClass;

/**
 * @internal
 */
final class AttributeSagaEventSubscriberResolverTest extends TestCase
{
    private AttributeSagaEventSubscriberResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeSagaEventSubscriberResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldReturnEmptyListWhenNoSubscribersAreResolved(): void
    {
        $definitions = $this->resolver->resolve(stdClass::class);

        self::assertSame([], $definitions);
    }

    #[Test]
    public function itShouldResolveSubscribers(): void
    {
        $definitions = $this->resolver->resolve(TestSaga::class);

        self::assertEquals([
            new SagaEventSubscriberDefinition(
                TestUseCaseCreatedEvent::class,
                'onTestUseCaseCreatedEvent',
                CreationPolicy::IfMissing,
            ),
            new SagaEventSubscriberDefinition(
                TestUseCaseModifiedEvent::class,
                'onTestUseCaseModifiedEvent',
                CreationPolicy::Never,
            ),
        ], $definitions);
    }
}

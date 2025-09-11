<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase\Default\EventSubscriber\Attribute;

use Gember\EventSourcing\Resolver\UseCase\EventSubscriberDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithSubscribers;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\UseCase\Default\EventSubscriber\Attribute\AttributeEventSubscriberResolver;
use Override;

/**
 * @internal
 */
final class AttributeEventSubscriberResolverTest extends TestCase
{
    private AttributeEventSubscriberResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeEventSubscriberResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldResolveEventSubscribers(): void
    {
        $definition = $this->resolver->resolve(TestUseCaseWithSubscribers::class);

        self::assertEquals([
            new EventSubscriberDefinition(
                TestUseCaseCreatedEvent::class,
                'onTestUseCaseCreatedEvent',
            ),
            new EventSubscriberDefinition(
                TestUseCaseModifiedEvent::class,
                'onTestUseCaseModifiedEvent',
            ),
        ], $definition);
    }
}

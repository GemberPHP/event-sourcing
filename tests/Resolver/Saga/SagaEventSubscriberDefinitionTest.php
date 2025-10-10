<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Saga;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class SagaEventSubscriberDefinitionTest extends TestCase
{
    #[Test]
    public function itShouldCreateFromPayload(): void
    {
        $definition = SagaEventSubscriberDefinition::fromPayload([
            'eventClassName' => TestUseCaseCreatedEvent::class,
            'methodName' => 'onTestUseCaseCreatedEvent',
            'policy' => 'if_missing',
        ]);

        self::assertEquals(new SagaEventSubscriberDefinition(
            TestUseCaseCreatedEvent::class,
            'onTestUseCaseCreatedEvent',
            CreationPolicy::IfMissing,
        ), $definition);
    }

    #[Test]
    public function itShouldSerializeToPayload(): void
    {
        $definition = new SagaEventSubscriberDefinition(
            TestUseCaseCreatedEvent::class,
            'onTestUseCaseCreatedEvent',
            CreationPolicy::IfMissing,
        );

        self::assertSame([
            'eventClassName' => TestUseCaseCreatedEvent::class,
            'methodName' => 'onTestUseCaseCreatedEvent',
            'policy' => 'if_missing',
        ], $definition->toPayload());
    }
}

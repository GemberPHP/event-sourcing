<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Saga;

use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdDefinition;
use Gember\EventSourcing\Resolver\Saga\SagaDefinition;
use Gember\EventSourcing\Resolver\Saga\SagaEventSubscriberDefinition;
use Gember\EventSourcing\Test\TestDoubles\Saga\TestSaga;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class SagaDefinitionTest extends TestCase
{
    #[Test]
    public function itShouldCreateFromPayload(): void
    {
        $definition = SagaDefinition::fromPayload([
            'sagaClassName' => TestSaga::class,
            'sagaName' => 'test.saga',
            'sagaId' => [
                'sagaIdName' => 'id',
            ],
            'eventSubscribers' => [
                [
                    'eventClassName' => TestUseCaseCreatedEvent::class,
                    'methodName' => 'onTestUseCaseCreatedEvent',
                    'policy' => 'if_missing',
                ],
                [
                    'eventClassName' => TestUseCaseModifiedEvent::class,
                    'methodName' => 'onTestUseCaseModifiedEvent',
                    'policy' => 'never',
                ],
            ],
        ]);

        self::assertEquals(
            new SagaDefinition(
                TestSaga::class,
                'test.saga',
                new SagaIdDefinition('id'),
                [
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
                ],
            ),
            $definition,
        );
    }

    #[Test]
    public function itShouldSerializeToPayload(): void
    {
        $definition = new SagaDefinition(
            TestSaga::class,
            'test.saga',
            new SagaIdDefinition('id'),
            [
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
            ],
        );

        self::assertSame([
            'sagaClassName' => TestSaga::class,
            'sagaName' => 'test.saga',
            'sagaId' => [
                'sagaIdName' => 'id',
            ],
            'eventSubscribers' => [
                [
                    'eventClassName' => TestUseCaseCreatedEvent::class,
                    'methodName' => 'onTestUseCaseCreatedEvent',
                    'policy' => 'if_missing',
                ],
                [
                    'eventClassName' => TestUseCaseModifiedEvent::class,
                    'methodName' => 'onTestUseCaseModifiedEvent',
                    'policy' => 'never',
                ],
            ],
        ], $definition->toPayload());
    }
}

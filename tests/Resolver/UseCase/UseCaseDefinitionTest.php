<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\Resolver\UseCase\EventSubscriberDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestSecondUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Common\CreationPolicy;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\UseCase\UseCaseDefinition;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class UseCaseDefinitionTest extends TestCase
{
    #[Test]
    public function itShouldSerializeAndDeserialize(): void
    {
        $definition = new UseCaseDefinition(
            TestUseCase::class,
            [
                new DomainTagDefinition('id', DomainTagType::Property),
                new DomainTagDefinition('anotherId', DomainTagType::Property),
            ],
            [
                new CommandHandlerDefinition(
                    TestUseCaseWithCommand::class,
                    '__invoke',
                    CreationPolicy::Never,
                ),
                new CommandHandlerDefinition(
                    TestSecondUseCaseWithCommand::class,
                    'method',
                    CreationPolicy::IfMissing,
                ),
            ],
            [
                new EventSubscriberDefinition(TestUseCaseCreatedEvent::class, '__invoke'),
                new EventSubscriberDefinition(TestUseCaseModifiedEvent::class, 'execute'),
            ],
        );

        $serialized = $definition->toPayload();

        self::assertSame([
            'useCaseClassName' => TestUseCase::class,
            'domainTags' => [
                [
                    'domainTagName' => 'id',
                    'type' => 'property',
                ],
                [
                    'domainTagName' => 'anotherId',
                    'type' => 'property',
                ],
            ],
            'commandHandlers' => [
                [
                    'commandClassName' => TestUseCaseWithCommand::class,
                    'methodName' => '__invoke',
                    'policy' => 'never',
                ],
                [
                    'commandClassName' => TestSecondUseCaseWithCommand::class,
                    'methodName' => 'method',
                    'policy' => 'if_missing',
                ],
            ],
            'eventSubscribers' => [
                [
                    'eventClassName' => TestUseCaseCreatedEvent::class,
                    'methodName' => '__invoke',
                ],
                [
                    'eventClassName' => TestUseCaseModifiedEvent::class,
                    'methodName' => 'execute',
                ],
            ],
        ], $serialized);

        $deserialized = UseCaseDefinition::fromPayload($serialized);

        self::assertEquals($deserialized, $definition);
    }
}

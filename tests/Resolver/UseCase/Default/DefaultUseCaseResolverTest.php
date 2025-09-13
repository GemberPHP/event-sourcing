<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase\Default;

use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\Resolver\UseCase\Default\CommandHandler\Attribute\AttributeCommandHandlerResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\EventSubscriber\Attribute\AttributeEventSubscriberResolver;
use Gember\EventSourcing\Resolver\UseCase\EventSubscriberDefinition;
use Gember\EventSourcing\Resolver\UseCase\UseCaseDefinition;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestFullUseCase;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestSecondUseCaseWithCommand;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\UseCase\Default\DefaultUseCaseResolver;
use Override;

/**
 * @internal
 */
final class DefaultUseCaseResolverTest extends TestCase
{
    private DefaultUseCaseResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new DefaultUseCaseResolver(
            new AttributeDomainTagResolver($attributeResolver = new ReflectorAttributeResolver()),
            new AttributeCommandHandlerResolver($attributeResolver),
            new AttributeEventSubscriberResolver($attributeResolver),
        );
    }

    #[Test]
    public function itShouldResolveUseCase(): void
    {
        $definition = $this->resolver->resolve(TestFullUseCase::class);

        self::assertEquals(new UseCaseDefinition(
            TestFullUseCase::class,
            [
                new DomainTagDefinition('domainTag', DomainTagType::Property),
                new DomainTagDefinition('secondaryId', DomainTagType::Property),
            ],
            [
                new CommandHandlerDefinition(
                    TestUseCaseWithCommand::class,
                    '__invoke',
                    CreationPolicy::Never,
                ),
                new CommandHandlerDefinition(
                    TestSecondUseCaseWithCommand::class,
                    'second',
                    CreationPolicy::IfMissing,
                ),
            ],
            [
                new EventSubscriberDefinition(
                    TestUseCaseCreatedEvent::class,
                    'onTestUseCaseCreatedEvent',
                ),
                new EventSubscriberDefinition(
                    TestUseCaseModifiedEvent::class,
                    'onTestUseCaseModifiedEvent',
                ),
            ],
        ), $definition);
    }
}

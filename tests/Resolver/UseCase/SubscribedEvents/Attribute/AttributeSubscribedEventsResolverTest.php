<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\UseCase\SubscribedEvents\Attribute;

use Gember\EventSourcing\Resolver\UseCase\SubscribedEvents\Attribute\AttributeSubscribedEventsResolver;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithSubscribers;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class AttributeSubscribedEventsResolverTest extends TestCase
{
    private AttributeSubscribedEventsResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeSubscribedEventsResolver(
            new ReflectorAttributeResolver(),
        );
    }

    #[Test]
    public function itShouldResolveAllDomainEventsUsedInUseCase(): void
    {
        $domainEvents = $this->resolver->resolve(TestUseCaseWithSubscribers::class); // @phpstan-ignore-line

        self::assertSame([
            TestUseCaseCreatedEvent::class,
            TestUseCaseModifiedEvent::class,
        ], $domainEvents);
    }
}

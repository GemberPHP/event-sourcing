<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Registry\Event\Reflector;

use Gember\EventSourcing\Registry\Event\EventNotRegisteredException;
use Gember\EventSourcing\Registry\Event\Reflector\ReflectorEventRegistry;
use Gember\EventSourcing\Resolver\Common\DomainTag\Attribute\AttributeDomainTagResolver;
use Gember\EventSourcing\Resolver\Common\SagaId\Attribute\AttributeSagaIdResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\DefaultDomainEventResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Attribute\AttributeEventNameResolver;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\Util\File\Finder\TestFinder;
use Gember\EventSourcing\Test\TestDoubles\Util\File\Reflector\TestReflector;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class ReflectorEventRegistryTest extends TestCase
{
    private TestFinder $finder;
    private TestReflector $reflector;
    private ReflectorEventRegistry $registry;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new ReflectorEventRegistry(
            $this->finder = new TestFinder(),
            $this->reflector = new TestReflector(),
            new DefaultDomainEventResolver(
                new AttributeEventNameResolver($attributeResolver = new ReflectorAttributeResolver()),
                new AttributeDomainTagResolver($attributeResolver),
                new AttributeSagaIdResolver($attributeResolver),
            ),
            'path',
        );
    }

    #[Test]
    public function itShouldThrowExceptionIfEventIsNotRegistered(): void
    {
        self::expectException(EventNotRegisteredException::class);

        $this->registry->retrieve('some-event');
    }

    #[Test]
    public function itShouldRetrieveEventFqcnBasedOnNormalizedEventName(): void
    {
        $this->finder->files = [
            'path/to/event.php',
            '',
        ];

        $this->reflector->files = [
            'path/to/event.php' => TestUseCaseCreatedEvent::class,
        ];

        $eventFqcn = $this->registry->retrieve('test.use-case.created');

        self::assertSame(TestUseCaseCreatedEvent::class, $eventFqcn);
    }
}

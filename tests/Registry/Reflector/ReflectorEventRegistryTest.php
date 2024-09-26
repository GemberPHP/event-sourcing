<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Registry\Reflector;

use Gember\EventSourcing\Registry\EventNotRegisteredException;
use Gember\EventSourcing\Registry\Reflector\ReflectorEventRegistry;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Attribute\AttributeNormalizedEventNameResolver;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
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
            new AttributeNormalizedEventNameResolver(new ReflectorAttributeResolver()),
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
            'path/to/event.php' => TestDomainContextCreatedEvent::class,
        ];

        $eventFqcn = $this->registry->retrieve('test.domain-context.created');

        self::assertSame(TestDomainContextCreatedEvent::class, $eventFqcn);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\Default\EventName\Stacked;

use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Attribute\AttributeEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\ClassName\ClassNameEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Interface\InterfaceEventNameResolver;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestClassNameBasedDomainEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\Stacked\StackedEventNameResolver;
use Override;

/**
 * @internal
 */
final class StackedEventNameResolverTest extends TestCase
{
    private StackedEventNameResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new StackedEventNameResolver(
            [
                new AttributeEventNameResolver(new ReflectorAttributeResolver()),
                new InterfaceEventNameResolver(),
            ],
            new ClassNameEventNameResolver(
                new NativeFriendlyClassNamer(new NativeInflector()),
            ),
        );
    }

    #[Test]
    public function itShouldResolveEventNames(): void
    {
        $eventName = $this->resolver->resolve(TestUseCaseCreatedEvent::class);
        self::assertSame('test.use-case.created', $eventName);

        $eventName = $this->resolver->resolve(TestUseCaseModifiedEvent::class);
        self::assertSame('test.use-case.modified', $eventName);

        $eventName = $this->resolver->resolve(TestClassNameBasedDomainEvent::class);
        self::assertSame('gember.event-sourcing.test.test-doubles.use-case.test-class-name-based-domain-event', $eventName);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\Default\EventName\ClassName;

use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\ClassName\ClassNameEventNameResolver;
use PHPUnit\Framework\Attributes\Test;
use Override;

/**
 * @internal
 */
final class ClassNameEventNameResolverTest extends TestCase
{
    private ClassNameEventNameResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new ClassNameEventNameResolver(
            new NativeFriendlyClassNamer(new NativeInflector()),
        );
    }

    #[Test]
    public function itShouldResolveEventNameFromClass(): void
    {
        $eventName = $this->resolver->resolve(TestUseCaseCreatedEvent::class);

        self::assertSame('gember.event-sourcing.test.test-doubles.use-case.test-use-case-created-event', $eventName);
    }
}

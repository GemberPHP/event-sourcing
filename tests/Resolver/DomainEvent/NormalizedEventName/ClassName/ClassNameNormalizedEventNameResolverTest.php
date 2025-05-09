<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainEvent\NormalizedEventName\ClassName;

use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\ClassName\ClassNameNormalizedEventNameResolver;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestClassNameBasedDomainEvent;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\Native\NativeFriendlyClassNamer;
use Gember\EventSourcing\Util\String\Inflector\Native\NativeInflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class ClassNameNormalizedEventNameResolverTest extends TestCase
{
    private ClassNameNormalizedEventNameResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new ClassNameNormalizedEventNameResolver(
            new NativeFriendlyClassNamer(new NativeInflector()),
        );
    }

    #[Test]
    public function itShouldResolveEventName(): void
    {
        $eventName = $this->resolver->resolve(TestClassNameBasedDomainEvent::class);

        self::assertSame(
            'gember.event-sourcing.test.test-doubles.use-case.test-class-name-based-domain-event',
            $eventName,
        );
    }
}

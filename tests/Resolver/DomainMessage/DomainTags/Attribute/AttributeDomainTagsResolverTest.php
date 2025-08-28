<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainMessage\DomainTags\Attribute;

use Gember\EventSourcing\Resolver\DomainMessage\DomainTags\Attribute\AttributeDomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainMessage\DomainTags\UnresolvableDomainTagsException;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class AttributeDomainTagsResolverTest extends TestCase
{
    private AttributeDomainTagsResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new AttributeDomainTagsResolver(new ReflectorAttributeResolver());
    }

    #[Test]
    public function itShouldThrowExceptionWhenDomainTagsCannotBeResolved(): void
    {
        self::expectException(UnresolvableDomainTagsException::class);

        $this->resolver->resolve(new TestUseCaseModifiedEvent());
    }

    #[Test]
    public function itShouldResolveDomainTags(): void
    {
        $domainTags = $this->resolver->resolve(new TestUseCaseCreatedEvent(
            '7cb5c1e5-be4d-4520-90ab-b2300cb67ae1',
            'fb08765f-2549-44b3-8b47-14a0dab158ea',
        ));

        self::assertSame([
            '7cb5c1e5-be4d-4520-90ab-b2300cb67ae1',
            'fb08765f-2549-44b3-8b47-14a0dab158ea',
        ], $domainTags);
    }
}

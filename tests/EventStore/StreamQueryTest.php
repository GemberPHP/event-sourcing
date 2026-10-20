<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\EventStore;

use DateTimeImmutable;
use Gember\EventSourcing\EventStore\StreamQuery;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestDomainTag;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseModifiedEvent;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\Metadata;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StreamQueryTest extends TestCase
{
    #[Test]
    public function itShouldMatchEventOfSubscribedClassWithOverlappingDomainTag(): void
    {
        $streamQuery = new StreamQuery([new TestDomainTag('tag-1'), 'tag-2'], [TestUseCaseCreatedEvent::class]);

        self::assertTrue($streamQuery->matches($this->envelope(new TestUseCaseCreatedEvent('a', 'b'), ['tag-1', 'tag-3'])));
        self::assertTrue($streamQuery->matches($this->envelope(new TestUseCaseCreatedEvent('a', 'b'), ['tag-2'])));
    }

    #[Test]
    public function itShouldNotMatchEventOfOtherClass(): void
    {
        $streamQuery = new StreamQuery(['tag-1'], [TestUseCaseCreatedEvent::class]);

        self::assertFalse($streamQuery->matches($this->envelope(new TestUseCaseModifiedEvent(), ['tag-1'])));
    }

    #[Test]
    public function itShouldNotMatchEventWithoutOverlappingDomainTag(): void
    {
        $streamQuery = new StreamQuery(['tag-1'], [TestUseCaseCreatedEvent::class]);

        self::assertFalse($streamQuery->matches($this->envelope(new TestUseCaseCreatedEvent('a', 'b'), ['tag-2'])));
        self::assertFalse($streamQuery->matches($this->envelope(new TestUseCaseCreatedEvent('a', 'b'), [])));
    }

    /**
     * @param list<string> $domainTags
     */
    private function envelope(object $event, array $domainTags): DomainEventEnvelope
    {
        return new DomainEventEnvelope('event-id', $domainTags, $event, new Metadata(), new DateTimeImmutable());
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\DomainContext;

use Gember\EventSourcing\DomainContext\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\DomainContext\Attribute\DomainId;
use Gember\EventSourcing\DomainContext\EventSourcedDomainContext;
use Gember\EventSourcing\DomainContext\EventSourcedDomainContextBehaviorTrait;

/**
 * @implements EventSourcedDomainContext<TestDomainContext>
 */
final class TestDomainContext implements EventSourcedDomainContext
{
    /**
     * @use EventSourcedDomainContextBehaviorTrait<TestDomainContext>
     */
    use EventSourcedDomainContextBehaviorTrait;

    #[DomainId]
    private TestDomainId $domainId;

    #[DomainId]
    private string $secondaryId;

    /**
     * @var list<object>
     */
    public array $testAppliedEvents = [];

    public static function create(TestDomainId $domainId, string $secondaryId): self
    {
        $domainContext = new self();
        $domainContext->apply(new TestDomainContextCreatedEvent((string) $domainId, $secondaryId));

        return $domainContext;
    }

    public function modify(): void
    {
        $this->apply(new TestDomainContextModifiedEvent());
    }

    #[DomainEventSubscriber]
    private function onTestDomainContextCreatedEvent(TestDomainContextCreatedEvent $event): void
    {
        $this->domainId = new TestDomainId($event->id);
        $this->secondaryId = $event->secondaryId;
        $this->testAppliedEvents[] = $event;
    }
}

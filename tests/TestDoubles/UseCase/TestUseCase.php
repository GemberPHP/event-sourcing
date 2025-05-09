<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\UseCase\Attribute\DomainId;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;

final class TestUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

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
        $useCase = new self();
        $useCase->apply(new TestUseCaseCreatedEvent((string) $domainId, $secondaryId));

        return $useCase;
    }

    public function modify(): void
    {
        $this->apply(new TestUseCaseModifiedEvent());
    }

    #[DomainEventSubscriber]
    private function onTestUseCaseCreatedEvent(TestUseCaseCreatedEvent $event): void
    {
        $this->domainId = new TestDomainId($event->id);
        $this->secondaryId = $event->secondaryId;
        $this->testAppliedEvents[] = $event;
    }
}

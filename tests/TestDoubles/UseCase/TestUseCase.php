<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;

final class TestUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private TestDomainTag $domainTag;

    #[DomainTag]
    private string $secondaryTag;

    /**
     * @var list<object>
     */
    public array $testAppliedEvents = [];

    public static function create(TestDomainTag $domainTag, string $secondaryTag): self
    {
        $useCase = new self();
        $useCase->apply(new TestUseCaseCreatedEvent((string) $domainTag, $secondaryTag));

        return $useCase;
    }

    public function modify(): void
    {
        $this->apply(new TestUseCaseModifiedEvent());
    }

    #[DomainEventSubscriber]
    private function onTestUseCaseCreatedEvent(TestUseCaseCreatedEvent $event): void
    {
        $this->domainTag = new TestDomainTag((string) $event->id);
        $this->secondaryTag = $event->secondaryId;
        $this->testAppliedEvents[] = $event;
    }
}

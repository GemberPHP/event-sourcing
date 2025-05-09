<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use stdClass;

final readonly class TestUseCaseWithSubscribers
{
    // @phpstan-ignore-next-line
    #[DomainEventSubscriber]
    private function onTestUseCaseCreatedEvent(TestUseCaseCreatedEvent $event): void {}

    // @phpstan-ignore-next-line
    private function missingAttribute(stdClass $event): void {}

    // @phpstan-ignore-next-line
    #[DomainEventSubscriber]
    private function missingParameters(): void {}

    // @phpstan-ignore-next-line
    #[DomainEventSubscriber]
    private function missingType($event): void {}

    // @phpstan-ignore-next-line
    #[DomainEventSubscriber]
    private function onTestUseCaseModifiedEvent(TestUseCaseModifiedEvent $event): void {}
}

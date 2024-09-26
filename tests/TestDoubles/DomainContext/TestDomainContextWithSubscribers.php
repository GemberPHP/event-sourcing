<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\DomainContext;

use Gember\EventSourcing\DomainContext\Attribute\DomainEventSubscriber;
use stdClass;

final readonly class TestDomainContextWithSubscribers
{
    // @phpstan-ignore-next-line
    #[DomainEventSubscriber]
    private function onTestDomainContextCreatedEvent(TestDomainContextCreatedEvent $event): void {}

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
    private function onTestDomainContextModifiedEvent(TestDomainContextModifiedEvent $event): void {}
}

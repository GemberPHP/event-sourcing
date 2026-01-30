# 🫚 Gember Event sourcing
[![Build Status](https://scrutinizer-ci.com/g/GemberPHP/event-sourcing/badges/build.png?b=main)](https://github.com/GemberPHP/event-sourcing/actions)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/GemberPHP/event-sourcing.svg?style=flat)](https://scrutinizer-ci.com/g/GemberPHP/event-sourcing/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/GemberPHP/event-sourcing.svg?style=flat)](https://scrutinizer-ci.com/g/GemberPHP/event-sourcing)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3-8892BF.svg?style=flat)](http://www.php.net)

_Use case driven EventSourcing - Let go of the Aggregate with the Dynamic Consistency Boundary (DCB) pattern._

## Documentation

- [Background](/docs/background.md)
- [Installation](/docs/installation.md)
- [Usage](/docs/usage.md)
  - [Commands](/docs/usage/commands.md) - Define commands that carry intent and domain tags for event retrieval
  - [Use cases / aggregates](/docs/usage/use-cases.md) - Model business logic using event-sourced use cases and traditional aggregates with DCB or aggregate patterns
  - [Command handlers](/docs/usage/command-handlers.md) - Trigger behavioral actions on use cases using command handlers
  - [Domain events](/docs/usage/domain-events.md) - Define and work with domain events, including naming, serialization, and domain tags
  - [Sagas](/docs/usage/sagas.md) - Implement long-running business processes that coordinate complex workflows across multiple domain events
- [How it works](/docs/how-it-works.md) - End-to-end flow, event store structure, CQRS, and the read side
- [Library architecture](/docs/library-architecture.md) - Internal code organization, design patterns, resolver and registry layers

## In a nutshell

#### Traditional 'Aggregate driven' EventSourcing

Domain concepts are modeled towards objects: the aggregate.

- Any business logic related to a single domain object should live inside the aggregate
- Logic that involves other domain objects or groups of the same kind of domain objects does not belong in the aggregate

<img width="1262" alt="aggregate-driven-event-sourcing" src="/docs/images/aggregate-driven-event-sourcing.png" />

#### 'Use case driven' EventSourcing
Domain concepts are modeled through use cases.

- Any business logic tied to a use case should live inside that use case
- A use case can relate to one or more domain concepts

<img width="495" alt="use-case-driven-event-sourcing" src="/docs/images/use-case-driven-event-sourcing.png" />

## A simple example

This example demonstrates all key features of the library: a DCB use case with command handler, domain events, and a saga coordinating a workflow.

**Scenario**: A student subscribes to a course. When subscription succeeds, a saga automatically sends a welcome email.

### Domain Events

```php
use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\Saga\Attribute\SagaId;

#[DomainEvent(name: 'course.created')]
final readonly class CourseCreatedEvent
{
    public function __construct(
        #[DomainTag]
        public string $courseId,
        public string $name,
    ) {}
}

#[DomainEvent(name: 'student.registered')]
final readonly class StudentRegisteredEvent
{
    public function __construct(
        #[DomainTag]
        public string $studentId,
        public string $email,
    ) {}
}

#[DomainEvent(name: 'student.subscribed')]
final readonly class StudentSubscribedEvent
{
    public function __construct(
        #[DomainTag]
        #[SagaId]  // Links to SubscriptionWelcomeSaga
        public string $courseId,
        #[DomainTag]
        #[SagaId]
        public string $studentId,
    ) {}
}
```

### Use Case with Command Handler

```php
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\UseCase\Attribute\DomainCommandHandler;
use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;

final class SubscribeStudentToCourse implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private CourseId $courseId;

    #[DomainTag]
    private StudentId $studentId;

    private bool $isSubscribed = false;

    /**
     * Subscribes a student to a course (DCB pattern with multiple domain tags).
     * Uses __invoke to emphasize this is a single-purpose use case.
     */
    #[DomainCommandHandler(policy: CreationPolicy::IfMissing)]
    public function __invoke(SubscribeStudentCommand $command): void
    {
        // 1. Check idempotency
        if ($this->isSubscribed) {
            return;
        }

        // 2. Protect invariants (simplified for example)
        // In real scenarios: check capacity, prerequisites, etc.

        // 3. Apply domain event
        $this->apply(new StudentSubscribedEvent(
            $command->courseId,
            $command->studentId,
        ));
    }

    #[DomainEventSubscriber]
    private function onCourseCreated(CourseCreatedEvent $event): void
    {
        $this->courseId = new CourseId($event->courseId);
    }

    #[DomainEventSubscriber]
    private function onStudentRegistered(StudentRegisteredEvent $event): void
    {
        $this->studentId = new StudentId($event->studentId);
    }

    #[DomainEventSubscriber]
    private function onStudentSubscribed(StudentSubscribedEvent $event): void
    {
        $this->isSubscribed = true;
    }
}
```

### Saga

```php
use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Saga\Attribute\SagaEventSubscriber;
use Gember\EventSourcing\Saga\Attribute\SagaId;

#[Saga(name: 'subscription.welcome')]
final class SubscriptionWelcomeSaga
{
    #[SagaId]
    public ?string $courseId = null;

    #[SagaId]
    public ?string $studentId = null;

    private bool $welcomeEmailSent = false;

    /**
     * When a student subscribes, automatically send a welcome email.
     */
    #[SagaEventSubscriber(policy: CreationPolicy::IfMissing)]
    public function onStudentSubscribed(StudentSubscribedEvent $event, CommandBus $commandBus): void
    {
        $this->courseId = $event->courseId;
        $this->studentId = $event->studentId;

        // Dispatch command to send welcome email
        $commandBus->handle(new SendWelcomeEmailCommand(
            $event->studentId,
            $event->courseId,
        ));

        $this->welcomeEmailSent = true;
    }
}
```

For more extended examples and complete implementations, check out the demo application [gember/example-event-sourcing-dcb](https://github.com/GemberPHP/example-event-sourcing-dcb).

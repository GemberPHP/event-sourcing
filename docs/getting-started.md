## Getting started

This guide walks you through building a minimal working example: a course that can be created via a command.

### 1. Install

```bash
composer require gember/event-sourcing-symfony-bundle
```

See [Installation](/docs/installation.md) for framework-specific details and configuration.

### 2. Define a domain event

Domain events describe what happened in your domain. They are immutable DTOs with `#[DomainTag]` attributes that link them to domain concepts.

```php
use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;

#[DomainEvent(name: 'course.created')]
final readonly class CourseCreatedEvent
{
    public function __construct(
        #[DomainTag]
        public string $courseId,
        public string $name,
    ) {}
}
```

### 3. Define a command

Commands represent the intent to change state. Their `#[DomainTag]` properties determine which events are loaded from the event store.

```php
use Gember\EventSourcing\UseCase\Attribute\DomainTag;

final readonly class CreateCourseCommand
{
    public function __construct(
        #[DomainTag]
        public string $courseId,
        public string $name,
    ) {}
}
```

### 4. Create a use case

The use case contains your business logic. It implements `EventSourcedUseCase`, defines domain tags matching the command, and uses event subscribers to rebuild state.

```php
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\UseCase\Attribute\DomainCommandHandler;
use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;

final class CreateCourse implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private string $courseId;

    private bool $created = false;

    #[DomainCommandHandler(policy: CreationPolicy::IfMissing)]
    public function __invoke(CreateCourseCommand $command): void
    {
        // Idempotency check
        if ($this->created) {
            return;
        }

        // Apply domain event
        $this->apply(new CourseCreatedEvent(
            $command->courseId,
            $command->name,
        ));
    }

    #[DomainEventSubscriber]
    private function onCourseCreated(CourseCreatedEvent $event): void
    {
        $this->courseId = $event->courseId;
        $this->created = true;
    }
}
```

**What happens here:**
- `#[DomainCommandHandler]` binds the command directly to this method — no separate handler class needed
- `CreationPolicy::IfMissing` creates a new use case instance when no prior events exist
- `$this->apply()` records the event and immediately calls matching event subscribers
- The event subscriber rebuilds the state (used for idempotency on subsequent calls)

### 5. Dispatch the command

From your application layer (e.g., a controller or service), dispatch the command via the message bus:

```php
$commandBus->handle(new CreateCourseCommand(
    courseId: 'course-123',
    name: 'Introduction to Event Sourcing',
));
```

That's it. The library automatically:
1. Extracts domain tags from the command
2. Queries the event store for matching events
3. Reconstitutes the use case from those events
4. Invokes the command handler method
5. Persists the new event to the event store
6. Publishes the event to the event bus

### What's next?

- [Domain events](/docs/usage/domain-events.md) - Naming, serialization, metadata, and domain tag strategies
- [Use cases / aggregates](/docs/usage/use-cases.md) - Cross-aggregate subscriptions, concurrency control, and the DCB pattern in depth
- [Sagas](/docs/usage/sagas.md) - Coordinate workflows across multiple use cases
- [How it works](/docs/how-it-works.md) - The full end-to-end flow and concurrency mechanism

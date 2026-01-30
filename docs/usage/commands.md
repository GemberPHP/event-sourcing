## Commands

Commands are immutable data transfer objects (DTOs) that represent an intent to change the system state. They carry all input data required to execute a use case and define the domain tags that determine which events are loaded from the event store.

### Basic setup

A command is a simple `readonly` class with public properties:

```php
use Gember\EventSourcing\UseCase\Attribute\DomainTag;

final readonly class SubscribeStudentCommand
{
    public function __construct(
        #[DomainTag]
        public string $courseId,
        #[DomainTag]
        public string $studentId,
    ) {}
}
```

### Domain tags on commands

The `#[DomainTag]` attribute on command properties determines **which events are loaded from the event store** when the command is dispatched. Gember builds a **stream query** from two inputs:

1. The **domain tag values** extracted from the command (scoping by domain concept)
2. The **event types** the use case has subscribed to via `#[DomainEventSubscriber]` (scoping by relevance)

The event store returns only events that match at least one of the domain tags **and** are of a type the use case subscribes to. This avoids loading events the use case has no interest in.

> **Important:** The command's domain tags **must match the use case's domain tags exactly**. The command tags determine which events are loaded; the use case tags determine the optimistic lock scope. If they differ, the lock scope won't match what was loaded, leading to incorrect concurrency behavior.

### Choosing domain tags

Use as few domain tags as possible. Each tag widens the optimistic lock scope - more tags mean more events are loaded and more potential for concurrency conflicts. Only add a tag when the use case genuinely needs events from that scope for validation.

Multiple tags use **OR logic**: events matching any tag (and any subscribed event type) are loaded.

### How domain tags work across components

Domain tags appear on commands, use cases, and events, each serving a different purpose:

| Component | Domain tag purpose |
|-----------|-------------------|
| **Command** | Which events to **load** from the event store |
| **Use case** | **Optimistic lock** scope (must match command) |
| **Event** | How to **index** the event for future retrieval |

### Best practices

- **Keep commands immutable** - Use `readonly` classes or properties
- **Use value objects for domain concepts** - IDs, time periods, etc. should use dedicated types rather than plain strings
- **No business logic** - Commands should contain no methods beyond the constructor
- **Domain tags match the use case** - Always ensure the same set of `#[DomainTag]` properties exists on both the command and the corresponding use case

## Domain events

Domain events are immutable messages that represent something that has happened in your domain. They are simple Data Transfer Objects (DTOs) with no business logic, capturing facts about state changes.

### Overview

In _Gember Event Sourcing_, any DTO can be used as a domain event. With DCB, domain events are not limited to a single aggregate root. Instead, the same event can be shared across multiple use cases and aggregates, enabling decentralized business logic.

### Domain tags

Domain tags are identifiers that link events to specific domain concepts (e.g., `Course`, `Student`). They enable the event sourcing system to:
- Route events to the correct use cases
- Query events by domain context
- Support DCB patterns with multiple domain concepts per use case

#### How domain tags work across components

Domain tags appear on commands, use cases, and events, each serving a different purpose:

| Component | Domain tag purpose |
|-----------|-------------------|
| **Command** | Which events to **load** from the event store |
| **Use case** | **Optimistic lock** scope (must match command) |
| **Event** | How to **index** the event for future retrieval |

> When loading a use case, Gember builds a **stream query** combining the domain tags (from the command) with the subscribed event types (from the use case's `#[DomainEventSubscriber]` methods). Only events matching at least one tag **and** a subscribed type are loaded.

An event's domain tags do not need to match the command's domain tags. They serve independent purposes. A command tag determines what gets loaded; an event tag determines how the event is discoverable in the future.

#### Choosing event domain tags

Add a domain tag for each use case that will need to load this event in the future. For example:

- If `CreateTimeslot` needs to see `TimeslotDiscardedEvent` via `practitionerId`, the event must have `practitionerId` as a domain tag
- If `PreReserveTimeslot` needs to see `TimeslotCreatedEvent` via `timeslotId`, the event must have `timeslotId` as a domain tag
- An event can have multiple domain tags if multiple use cases need it via different identifiers

There are two ways to define domain tags on events:

#### Option 1: Using the #[DomainTag] attribute

Mark properties with the `#[DomainTag]` attribute. This is the most common and recommended approach for simple scenarios:

```php
use Gember\EventSourcing\UseCase\Attribute\DomainTag;

final readonly class CourseCreatedEvent
{
    public function __construct(
        #[DomainTag]
        public string $courseId,
        public string $name,
        public int $capacity,
    ) {}
}
```

**Key points:**
- Multiple properties can be marked with `#[DomainTag]`
- Only annotated properties are used as domain tags
- Works well when domain tags map directly to constructor properties

#### Option 2: Implementing the SpecifiedDomainTags interface

For more control over domain tag extraction, implement the `SpecifiedDomainTags` interface:

```php
use Gember\EventSourcing\UseCase\SpecifiedDomainTags;

final readonly class ComplexEvent implements SpecifiedDomainTags
{
    public function __construct(
        public string $compositeId,
        public string $secondaryId,
        public int $value,
    ) {}

    /**
     * @return list<string|Stringable>
     */
    public function getDomainTags(): array
    {
        // Custom logic to extract or transform domain tags
        return [
            $this->compositeId,
            $this->secondaryId,
        ];
    }
}
```

**Key points:**
- Provides full control over domain tag extraction
- Can return computed or transformed values
- **Completely replaces attribute-based resolution** - if this interface is implemented, `#[DomainTag]` attributes are ignored
- Useful when domain tags don't map directly to properties or require transformation

### Event naming

The event name is a unique identifier used to match stored events with their corresponding PHP classes during deserialization. _Gember Event Sourcing_ provides three ways to define event names, checked in this order:

#### 1. Using the #[DomainEvent] attribute

Define the event name explicitly using the `#[DomainEvent]` attribute:

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

This is the recommended approach as it provides explicit control and makes refactoring safer.

#### 2. Implementing the NamedDomainEvent interface

For dynamic event naming or when you need more control, implement the `NamedDomainEvent` interface:

```php
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\UseCase\NamedDomainEvent;

final readonly class CourseCreatedEvent implements NamedDomainEvent
{
    public function __construct(
        #[DomainTag]
        public string $courseId,
        public string $name,
    ) {}

    public static function getName(): string
    {
        return 'course.created';
    }
}
```

#### 3. Fallback to FQCN

If neither the attribute nor the interface is used, _Gember Event Sourcing_ automatically generates a readable event name from the class's Fully Qualified Class Name (FQCN).

For example: `App\Domain\Course\CourseCreatedEvent` might become `app.domain.course.course-created-event`.

> **Best practice:** Always use explicit naming (attribute or interface) to maintain control over event names, especially when refactoring or reorganizing code.

### Serialization

Events must be serializable to be stored in the event store. _Gember Event Sourcing_ uses a configurable `Serializer` to convert events to and from their stored representation (typically JSON).

There are two approaches to event serialization:

#### Option 1: Automatic serialization (Recommended)

When using framework integrations like [gember/event-sourcing-symfony-bundle](https://github.com/GemberPHP/event-sourcing-symfony-bundle), events are automatically serialized using the Symfony Serializer component. This means **you don't need to implement any interface** - just define your events as simple DTOs:

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
        public int $capacity,
    ) {}
}
```

**Key benefits:**
- **Zero boilerplate** - No interface implementation required
- **Automatic handling** - Works with any DTO structure
- **Complex type support** - Can handle `DateTimeImmutable`, nested objects, and more
- **Framework integration** - Configured automatically by the Symfony bundle

> **Note:** This is the **recommended approach** for most applications, especially when using Symfony.

#### Option 2: Explicit serialization with Serializable interface

For more control over serialization or when not using a framework integration, implement the `Serializable` interface:

```php
use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @implements Serializable<array{
 *     courseId: string,
 *     name: string,
 *     capacity: int
 * }, CourseCreatedEvent>
 */
#[DomainEvent(name: 'course.created')]
final readonly class CourseCreatedEvent implements Serializable
{
    public function __construct(
        #[DomainTag]
        public string $courseId,
        public string $name,
        public int $capacity,
    ) {}

    /**
     * Convert the event to an array for storage.
     */
    public function toPayload(): array
    {
        return [
            'courseId' => $this->courseId,
            'name' => $this->name,
            'capacity' => $this->capacity,
        ];
    }

    /**
     * Reconstruct the event from stored data.
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['courseId'],
            $payload['name'],
            $payload['capacity'],
        );
    }
}
```

**Key points about the `Serializable` interface:**

- **Generic type parameters** - Document the payload structure and return type using PHPStan/Psalm annotations
  - First parameter: The array shape (`array{courseId: string, ...}`)
  - Second parameter: The implementing class (`CourseCreatedEvent`)
- **`toPayload()`** - Converts the event to an array for JSON serialization
- **`fromPayload()`** - Static factory method to reconstruct the event from stored data
- **When to use** - Useful when you need explicit control, want to avoid framework dependencies, or are not using a framework integration

#### How the StackedSerializer works

When using the Symfony bundle, both serialization approaches work together via the `StackedSerializer`:

1. **First**, it tries `SerializableInterfaceSerializer` - for events implementing `Serializable`
2. **Then**, it falls back to `SymfonySerializer` - for automatic reflection-based serialization
3. **Finally**, if both fail, it throws an exception with detailed error information

This means you can mix both approaches in your application - some events with explicit `Serializable` implementation, others with automatic serialization.

#### Best practices for event properties

Events are serialized and stored permanently in the event store. Use **primitive types** (`string`, `int`, `float`, `bool`, `DateTimeImmutable`) for all properties rather than domain Value Objects. This ensures:

- **Simple serialization** - Primitives serialize to JSON without custom logic
- **Schema evolution** - Historical events remain readable even when domain classes change
- **No replay dependencies** - Events can be deserialized without depending on domain model classes

```php
// Good: primitive types
#[DomainEvent(name: 'timeslot.created')]
final readonly class TimeslotCreatedEvent
{
    public function __construct(
        #[DomainTag]
        public string $timeslotId,
        #[DomainTag]
        public string $practitionerId,
        public DateTimeImmutable $startAt,
        public DateTimeImmutable $endAt,
    ) {}
}

// Avoid: Value Objects in events
final readonly class TimeslotCreatedEvent
{
    public function __construct(
        public TimeslotId $timeslotId,      // couples event to domain class
        public TimePeriod $timePeriod,       // breaks if TimePeriod structure changes
    ) {}
}
```

Additional guidelines:

1. **Keep events flat** - Avoid deeply nested structures
2. **Make events readonly** - Use `readonly` classes or properties to ensure immutability
3. **Include all data** - An event should carry everything needed to describe the change, so consumers don't need to look up additional data

#### Choosing between approaches

**Use automatic serialization when:**
- You're using Symfony or another framework with serializer integration
- You want minimal boilerplate code
- You prefer convention over explicit configuration

**Use the Serializable interface when:**
- You need explicit control over the serialization format
- You want to avoid framework dependencies
- You're working without a framework integration
- You prefer explicit, testable code

#### Custom serializers

For advanced scenarios (e.g., encryption, compression, custom formats), you can provide custom serializer implementations by implementing the `Serializer` interface from `gember/dependency-contracts`. These can be added to the `StackedSerializer` chain alongside the built-in serializers.

### Saga IDs on events

When using [Sagas](/docs/usage/sagas.md), domain events can carry `#[SagaId]` attributes to route events to the correct saga instance. The `#[SagaId]` and `#[DomainTag]` attributes are independent concerns - a property can have both, one, or neither:

| Attribute | Purpose | Used by | Storage |
|-----------|---------|---------|---------|
| `#[DomainTag]` | Event store indexing for use case loading | Use cases | Event store relations |
| `#[SagaId]` | Saga routing and identification | Sagas | Saga store relations |

```php
use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;

#[DomainEvent(name: 'student.subscribed')]
final readonly class StudentSubscribedEvent
{
    public function __construct(
        #[DomainTag]
        #[SagaId]  // Routes to saga AND indexes in event store
        public string $courseId,
        #[DomainTag]
        #[SagaId]
        public string $studentId,
    ) {}
}
```

A property with only `#[SagaId]` (no `#[DomainTag]`) routes to a saga but is not indexed for use case loading. A property with only `#[DomainTag]` (no `#[SagaId]`) is indexed in the event store but does not trigger any saga.

See [Sagas](/docs/usage/sagas.md) for complete documentation on saga routing.

### Event envelope

When stored, events are wrapped in a `DomainEventEnvelope` containing:

| Field | Type | Description |
|-------|------|-------------|
| `eventId` | `string` | Unique event ID |
| `domainTags` | `array` | Identifiers from `#[DomainTag]` properties |
| `event` | `object` | The actual event instance |
| `metadata` | `Metadata` | Additional context (e.g., user, correlation ID) |
| `appliedAt` | `DateTimeImmutable` | When the event was applied |

The event store persists two types of data:
- The **event** itself (ID, name, serialized payload, metadata, timestamp)
- The **domain tag relations** linking the event to its domain tags, enabling efficient retrieval by tag

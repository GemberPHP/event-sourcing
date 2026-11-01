## Domain events

Domain events are immutable messages that represent something that has happened in your domain. They are simple Data Transfer Objects (DTOs) with no business logic, capturing facts about state changes.

### Overview

In _Gember Event Sourcing_, any DTO can be used as a domain event. With DCB, domain events are not limited to a single aggregate root. Instead, the same event can be shared across multiple use cases and aggregates, enabling decentralized business logic.

### Domain tags

Domain tags are identifiers that link events to specific domain concepts (e.g., `Course`, `Student`). They enable the event sourcing system to:
- Route events to the correct use cases
- Query events by domain context
- Support DCB patterns with multiple domain concepts per use case

An event's domain tags index the event for the use cases that load it later (see [How it works - Domain tags across components](../how-it-works.md#domain-tags-across-components)). They do not need to match the command's domain tags. They serve independent purposes. A command tag determines what gets loaded; an event tag determines how the event is discoverable in the future.

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
- **Only used when the event has no `#[DomainTag]` properties** - attributes take precedence, so don't combine both on one event

**When to use `SpecifiedDomainTags` over `#[DomainTag]` attributes:**
- Domain tags need to be **derived or computed** from multiple properties (e.g., a composite key)
- Domain tags require **conditional logic** (e.g., only include a tag when a property is non-null)
- Domain tags need **transformation** before use (e.g., prefixing, hashing, or formatting)

For most events where domain tags map directly to constructor properties, the `#[DomainTag]` attribute is simpler and recommended.

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

Event classes must be located under the configured event registry path (`registry.event.reflector.path`, `src/` by default): the registry finds the class of a stored event by its name there. Events without domain tags are stored, but no use case can load them.

### Changing events

Stored events are never rewritten, so every version of an event that was ever stored must stay readable (there is no upcasting yet):

- **Keep the event name** when renaming or moving the class (use the attribute or interface, not the FQCN fallback)
- **Add new properties as nullable or with a default value**, so older payloads without them still deserialize
- **Handle older payload shapes in `fromPayload()`** when the event implements `Serializable`
- **Introduce a new event** for a change in meaning, and let subscribers handle both events

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

No boilerplate is needed, and types such as `DateTimeImmutable` and nested objects are handled. This is the **recommended approach** for most applications.

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

`toPayload()` converts the event to an array, `fromPayload()` reconstructs it from stored data; the generic type parameters document the payload shape and the class. Use this approach for explicit control over the stored format (e.g. to read older payloads, see [Changing events](#changing-events)), or without a framework integration.

Both approaches can be mixed: the `StackedSerializer` uses `toPayload()`/`fromPayload()` for classes implementing `Serializable`, and the framework serializer for the others.

#### Best practices for event properties

Events are serialized and stored permanently in the event store. Use **primitive types** (`string`, `int`, `float`, `bool`, `DateTimeImmutable`) for all properties rather than domain Value Objects. This ensures:

- **Simple serialization** - Primitives serialize to JSON without custom logic
- **Stable payloads** - Historical events remain readable even when domain classes change
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

#### Custom serializers

For advanced scenarios (e.g., encryption, compression, custom formats), you can provide custom serializer implementations by implementing the `Serializer` interface from `gember/dependency-contracts`. These can be added to the `StackedSerializer` chain alongside the built-in serializers (with the Symfony bundle, by overriding its serializer service).

### Saga IDs on events

When using [Sagas](sagas.md), domain events can carry `#[SagaId]` attributes to route events to the correct saga instance. The `#[SagaId]` and `#[DomainTag]` attributes are independent concerns - a property can have both, one, or neither:

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

See [Sagas](sagas.md) for complete documentation on saga routing.

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

### Metadata

The `Metadata` class is an immutable key-value container attached to each `DomainEventEnvelope`. It allows you to enrich events with contextual information that is not part of the domain event itself, such as correlation IDs, user context, or request tracing data.

#### How metadata flows

1. **Created empty** — When a use case is saved, the repository wraps each applied event in an envelope with empty metadata (`DomainEventEnvelopeFactory`) and passes the envelopes to `EventStore::append()`
2. **Enriched before persistence** — Metadata can only be added in a decorator around the `EventStore` (in `append()`): the envelopes are created inside the repository's `save()`, so a repository decorator or message bus middleware never sees them
3. **Stored alongside the event** — The metadata array is persisted as part of the event record
4. **Available when loading** — `EventStore::load()` returns the envelopes including their metadata. Use case subscribers and event bus handlers receive the event only, without metadata

#### Enriching events with metadata

The `DomainEventEnvelope` provides a `withMetadata()` method that returns a new envelope with replaced metadata. Use it in an `EventStore` decorator to enrich events before they are persisted:

```php
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\Metadata;

// Create enriched envelope (immutable - returns a new instance)
$enrichedEnvelope = $envelope->withMetadata(
    $envelope->metadata->addMetadata([
        'correlationId' => $this->correlationIdProvider->get(),
        'userId' => $this->securityContext->getUserId(),
    ]),
);
```

#### Common metadata use cases

| Key | Purpose |
|-----|---------|
| `correlationId` | Links related events across use cases and services |
| `causationId` | Identifies the event that caused this event |
| `userId` | The user who triggered the action |
| `requestId` | Traces the originating HTTP request |
| `userAgent` | Client information for audit trails |

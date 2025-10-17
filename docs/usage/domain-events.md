## Domain events

Domain events are immutable messages that represent something that has happened in your domain. They are simple Data Transfer Objects (DTOs) with no business logic, capturing facts about state changes.

### Overview

In _Gember Event Sourcing_, any DTO can be used as a domain event. With DCB, domain events are not limited to a single aggregate root. Instead, the same event can be shared across multiple use cases and aggregates, enabling decentralized business logic.

### Domain tags

Domain tags are identifiers that link events to specific domain concepts (e.g., `Course`, `Student`). They enable the event sourcing system to:
- Route events to the correct use cases
- Query events by domain context
- Support DCB patterns with multiple domain concepts per use case

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

When designing events, follow these guidelines for serialization:

1. **Use primitive types** - Prefer `string`, `int`, `float` and `bool` for all properties
2. **Keep events flat** - Avoid deeply nested structures
3. **Make events readonly** - Use `readonly` classes or properties to ensure immutability
4. **Avoid Value Objects** - While tempting, using domain Value Objects in events creates several issues:
   - **Serialization complexity** - Value Objects need custom serialization logic
   - **Version evolution** - If a Value Object's structure changes, historical events may break
   - **Coupling** - Events become coupled to domain model changes

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

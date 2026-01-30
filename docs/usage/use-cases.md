## Use cases / aggregates

As mentioned in the [Background](/docs/background.md) section, _Gember Event Sourcing_ lets you model both **use cases** using DCB and traditional **aggregates**.

### Basic setup

Both use cases and aggregates follow the same setup approach: they implement the `EventSourcedUseCase` interface.
A trait `EventSourcedUseCaseBehaviorTrait` is available to provide all required interface logic.

```php
final class SomeBusinessUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    // Your business logic goes here
}
```

### Domain tags

When using DCB, each use case is built from a stream of events tied to a set of **domain tags**. Domain tags are identifiers that link events to specific domain concepts (e.g., `CourseId`, `StudentId`).

To enable this (including features like optimistic locking), the use case needs to define all domain tags it is connected to.
This is done with the `#[DomainTag]` attribute on one or more properties.

> **Note:** For a traditional aggregate, there is always exactly **one** domain tag representing the aggregate's identity.

```php
final class SomeBusinessUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private SomeId $someId;

    #[DomainTag]
    private AnotherId $anotherId;

    // Your business logic goes here
}
```

#### Domain tag purposes

Domain tags appear on commands, use cases, and events, each serving a different purpose:

| Component | Domain tag purpose |
|-----------|-------------------|
| **Command** | Which events to **load** from the event store |
| **Use case** | **Optimistic lock** scope (must match command) |
| **Event** | How to **index** the event for future retrieval |

> **Important:** The use case's domain tags **must match the command's domain tags exactly**. The use case tags determine the optimistic lock scope; the command tags determine which events are loaded. If they differ, the lock scope won't match what was loaded, leading to incorrect concurrency behavior.

See [Commands](/docs/usage/commands.md) for more detail on command domain tags.

#### Choosing domain tags

Use as few domain tags as possible. Each tag widens the optimistic lock scope - more tags mean more events are loaded and more potential for concurrency conflicts. Only add a tag when the use case genuinely needs events from that scope for validation.

#### Documenting domain tag choices

It is recommended to add a class-level docblock to each use case explaining what it does and why each domain tag was chosen:

```php
/**
 * Creates a timeslot for a practitioner at a location.
 *
 * PractitionerId is a domain tag to load all timeslot events for this practitioner,
 * enabling overlap validation across timeslots.
 *
 * LocationId is a domain tag to load LocationCreatedEvent and validate location existence.
 */
final class CreateTimeslot implements EventSourcedUseCase
{
    // ...
}
```

### Behavioral methods

Use cases and aggregates contain behavioral methods that execute business logic.
These methods typically follow three main steps:

1. Check for idempotency - Ensure the action hasn't already been performed
2. Protect invariants - Validate business rules
3. Apply a domain event - Record what happened by calling `$this->apply($event)`

The `apply()` method does two things:
- In the current request: Immediately calls any matching event subscriber to update state
- For persistence: Queues the event to be saved to the event store

```php
public function doSomething(): void
{
    // 1. Check for idempotency
    if ($this->alreadyDone) {
        return;
    }

    // 2. Protect invariants (business rules)
    if (!$this->isValid()) {
        throw new InvalidOperationException();
    }

    // 3. Apply a domain event
    $this->apply(new SomethingDoneEvent(/*...*/));
}
```

> **Note:** To trigger these behavioral methods from your application, use command handlers. See [Command handlers](/docs/usage/command-handlers.md) for details on how to set up command handling.

### Event subscribers and state management

To check for idempotency and protect invariants, the use case needs to maintain a **domain state**.
This means keeping all data required to make business decisions.

The use case defines **event subscribers** using the `#[DomainEventSubscriber]` attribute.
When the use case is loaded from the repository, events matching the domain tags **and** the subscribed event types are replayed through these subscribers to rebuild the state.

**Key points:**
- The use case doesn't have to be the one that applied the event - it just needs to be related to at least one of the use case's domain tags
- The use case doesn't need an event subscriber for each applied event - only for events required to maintain decision-making state
- Event subscriber method names can be anything; the event type itself is used for matching (unlike some libraries that rely on method naming conventions)

```php
final class SomeBusinessUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private SomeId $someId;

    #[DomainTag]
    private AnotherId $anotherId;

    // State properties - all data required to make decisions
    private bool $alreadyDone = false;
    private SomeStatus $status;

    #[DomainEventSubscriber]
    private function onSomethingDoneEvent(SomethingDoneEvent $event): void
    {
        $this->someId = new SomeId($event->someId);
        $this->alreadyDone = true;
    }

    #[DomainEventSubscriber]
    private function onStatusChangedEvent(StatusChangedEvent $event): void
    {
        $this->status = SomeStatus::from($event->status);
    }
}
```

#### Cross-aggregate event subscriptions

A use case can subscribe to events applied by **other** use cases or aggregates. The event store query is built from both the domain tags and the subscribed event types (via `#[DomainEventSubscriber]`). If the use case subscribes to an event type that shares at least one domain tag, those events are loaded automatically - regardless of which use case originally applied them.

For example, a `CreateTimeslot` use case with `practitionerId` as a domain tag will automatically receive `PractitionerCreatedEvent` (applied by a different use case) because that event is also tagged with `practitionerId`:

```php
final class CreateTimeslot implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private PractitionerId $practitionerId;

    // This event was applied by a different use case (e.g., CreatePractitioner),
    // but it shares the practitionerId domain tag, so it's included when
    // this use case is loaded.
    #[DomainEventSubscriber]
    private function onPractitionerCreated(PractitionerCreatedEvent $event): void
    {
        $this->practitionerId = new PractitionerId($event->practitionerId);
    }
}
```

This is a key benefit of the DCB pattern: events are not locked to a single aggregate and can be reused across multiple use cases through shared domain tags.

#### Documenting event subscribers

It is recommended to document each event subscriber with a docblock explaining:
1. **Why** it exists (what state it reconstructs and for what validation)
2. **Which domain tag** causes the event to be loaded for this use case

```php
/**
 * Tracks active time periods for the practitioner to validate
 * that a new timeslot does not overlap with existing ones.
 * Loaded via domain tag: practitionerId.
 */
#[DomainEventSubscriber]
private function onTimeslotCreated(TimeslotCreatedEvent $event): void
{
    // ...
}
```

### How it works

When you retrieve a use case from the repository:

1. The repository builds a stream query from the domain tags and the use case's subscribed event types, then queries the event store
2. Events are replayed in chronological order through the event subscribers
3. Each subscriber updates the internal state
4. The fully reconstructed use case is returned, ready for business logic execution

When you save a use case:

1. All events applied during the request (via `$this->apply()`) are persisted to the event store
2. These events are linked to the domain tags defined in the use case
3. An optimistic lock check is performed using a stream query built from the use case's domain tags and subscribed event types - the same query used during loading. If new events matching this query have been added since the use case was loaded, the save is rejected

### Examples

#### Example 1: DCB use case with multiple domain tags

This example demonstrates a use case using DCB. It tracks whether a student is subscribed to a course by listening to events from both the `Course` and `Student` concepts.

**Key characteristics:**
- Uses **two domain tags** (`CourseId` and `StudentId`)
- Subscribes to events from concepts Course and Student
- Makes a business decision based on multiple contexts

> **Note:** When modeling your use case using DCB, it is recommended to only have one behavioral method reflecting the use case.
> To make this explicit and very clear, you can use `__invoke` as method name.

```php
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

    private bool $isStudentSubscribedToCourse = false;

    public function subscribe(): void
    {
        // 1. Check idempotency
        if ($this->isStudentSubscribedToCourse) {
            return;
        }

        // 2. Protect invariants (business rules)
        // For example: check if course has capacity, student is eligible, etc.
        // ...

        // 3. Apply domain event
        $this->apply(new StudentSubscribedToCourseEvent(
            (string) $this->courseId,
            (string) $this->studentId,
        ));
    }

    #[DomainEventSubscriber]
    private function onCourseCreatedEvent(CourseCreatedEvent $event): void
    {
        $this->courseId = new CourseId($event->courseId);
    }

    #[DomainEventSubscriber]
    private function onStudentCreatedEvent(StudentCreatedEvent $event): void
    {
        $this->studentId = new StudentId($event->studentId);
    }

    #[DomainEventSubscriber]
    private function onStudentSubscribedToCourseEvent(StudentSubscribedToCourseEvent $event): void
    {
        $this->isStudentSubscribedToCourse = true;
    }

    #[DomainEventSubscriber]
    private function onStudentUnsubscribedFromCourseEvent(StudentUnsubscribedFromCourseEvent $event): void
    {
        $this->isStudentSubscribedToCourse = false;
    }
}
```

#### Example 2: Traditional aggregate root

This example shows a traditional aggregate root pattern. The `Course` aggregate is the single source of truth for course data and manages its own lifecycle.

**Key characteristics:**
- Uses **one domain tag** (`CourseId`) representing the aggregate identity
- Only subscribes to its own events
- Manages its own complete state

```php
use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;

final class Course implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private CourseId $courseId;

    private string $name;
    private int $capacity;

    public static function create(CourseId $courseId, string $name, int $capacity): self
    {
        // Static factory method for creating new aggregates
        $course = new self();
        $course->apply(new CourseCreatedEvent((string) $courseId, $name, $capacity));

        return $course;
    }

    public function rename(string $name): void
    {
        // 1. Check idempotency
        if ($this->name === $name) {
            return;
        }

        // 2. Protect invariants (business rules)
        if (empty($name)) {
            throw new InvalidArgumentException('Course name cannot be empty');
        }

        // 3. Apply domain event
        $this->apply(new CourseRenamedEvent((string) $this->courseId, $name));
    }

    public function changeCapacity(int $capacity): void
    {
        // 1. Check idempotency
        if ($this->capacity === $capacity) {
            return;
        }

        // 2. Protect invariants
        if ($capacity < 1) {
            throw new InvalidArgumentException('Course capacity must be at least 1');
        }

        // 3. Apply domain event
        $this->apply(new CourseCapacityChangedEvent((string) $this->courseId, $capacity));
    }

    #[DomainEventSubscriber]
    private function onCourseCreatedEvent(CourseCreatedEvent $event): void
    {
        $this->courseId = new CourseId($event->courseId);
        $this->name = $event->name;
        $this->capacity = $event->capacity;
    }

    #[DomainEventSubscriber]
    private function onCourseRenamedEvent(CourseRenamedEvent $event): void
    {
        $this->name = $event->name;
    }

    #[DomainEventSubscriber]
    private function onCourseCapacityChangedEvent(CourseCapacityChangedEvent $event): void
    {
        $this->capacity = $event->capacity;
    }
}
```

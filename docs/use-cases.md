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

To trigger these behavioral methods from your application, use command handlers. See [Command handlers](/docs/command-handlers.md) for details on how to set up command handling.

### Event subscribers and state management

To check for idempotency and protect invariants, the use case needs to maintain a **domain state**.
This means keeping all data required to make business decisions.

The use case defines **event subscribers** using the `#[DomainEventSubscriber]` attribute.
When the use case is loaded from the repository, all events matching the domain tags are replayed through these subscribers to rebuild the state.

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

### How it works

When you retrieve a use case from the repository:

1. The repository queries the event store for all events matching the domain tags
2. Events are replayed in chronological order through the event subscribers
3. Each subscriber updates the internal state
4. The fully reconstructed use case is returned, ready for business logic execution

When you save a use case:

1. All events applied during the request (via `$this->apply()`) are persisted to the event store
2. These events are linked to the domain tags defined in the use case
3. An optimistic lock prevents concurrent modifications to the same event stream

### Examples

#### Example 1: DCB use case with multiple domain tags

This example demonstrates a use case using DCB. It tracks whether a student is subscribed to a course by listening to events from both the `Course` and `Student` concepts.

**Key characteristics:**
- Uses **two domain tags** (`CourseId` and `StudentId`)
- Subscribes to events from concepts Course and Student
- Makes a business decision based on multiple contexts

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

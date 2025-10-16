## Use cases / aggregates

Like mentioned in the [Background](/docs/background.md) section, _Gember Event Sourcing_ lets you model both **use cases** using DCB and traditional **aggregates**.

The setup for both are pretty much the same; they just need to implement the `EventSourcedUseCase` interface.
A trait `EventSourcedUseCaseBehaviorTrait` is available for all required interface logic.

```php
final class SomeBusinessUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    // Do your magic
}
```

When using DCB, each model is built from a specific stream of events tied to a set of **domain tags**.

To make this work behind the scenes (e.g. optimistic lock guards), the model needs to define all domain tags it is connected to.
This can be done with the `#[DomainTag]` attribute on one or more (private) properties.

> Note: For a traditional aggregate, this is always just **one** domain tag.

```php
final class SomeBusinessUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private SomeId $someId;

    #[DomainTag]
    private AnotherId $anotherId;

    // Do your magic
}
```

Next up is to add behavior to the model; primarily done in the form of methods.
These methods typically consists of three main steps:

1. Check for idempotency
2. Protect invariants (business rules)
3. Apply a domain event

To trigger these behavioral methods, you can use command handlers. See [Command handlers](/docs/command-handlers.md) for more details on how to set up command handling.

In order to check for idempotency and protect invariants, the model needs maintain a domain state.
This basically means that it needs to keep all data required to make these decisions.

Therefore, the model can define **event subscribers** with the `#[DomainEventSubscriber]` attribute.
Any event subscribed in this way is automatically loaded from the event store when building the model.

> Note: The model doesn't have to be the one that applied the event. It just needs to be related to at least one of the model's domain tags.
>
> Also, the model doesn't need to have an event subscriber for each applied message. Just for the events which are required to maintain domain state.

```php
final class SomeBusinessUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private SomeId $someId;

    #[DomainTag]
    private AnotherId $anotherId;

    // All required data to make decisions with
    private SomeStatus $status;
    // ...

    #[DomainEventSubscriber]
    private function applyModelOpenedEvent(ModelOpenedEvent $event) : void
    {
        // Update state
    }

    /*
     * FYI: In a lot of event sourcing libraries, the method name is used to match the event.
     * That's not needed here. The event type itself is used. The method name can be anything.
     */
    #[DomainEventSubscriber]
    private function applyModelArchivedEvent(ModelArchivedEvent $event) : void
    {
        // Update state
    }
}
```

### Examples

A simple example of a business decision model using several domain tags and events:

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

    private bool $isStudentSubscribedToCourse;

    public function subscribe(): void
    {
        // Check idempotency
        if ($this->isStudentSubscribedToCourse) {
            return;
        }

        // Protect invariants
        // ...

        $this->apply(new StudentSubscribedToCourseEvent((string) $this->courseId, (string) $this->studentId));
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

A simple example of a traditional aggregate root:

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

    public static function create(CourseId $courseId, string $name, int $capacity): self
    {
        $course = new self();
        $course->apply(new CourseCreatedEvent((string) $courseId, $name, $capacity));

        return $course;
    }

    public function rename(string $name): void
    {
        // Check idempotency
        if ($this->name === $name) {
            return;
        }

        // Protect invariants
        // ...

        $this->apply(new CourseRenamedEvent((string) $this->courseId, $name));
    }

    #[DomainEventSubscriber]
    private function onCourseCreatedEvent(CourseCreatedEvent $event): void
    {
        $this->courseId = new CourseId($event->courseId);
        $this->name = $event->name;
    }

    #[DomainEventSubscriber]
    private function onCourseNameChangedEvent(CourseRenamedEvent $event): void
    {
        $this->name = $event->name;
    }
}
```

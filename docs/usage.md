## Usage

### Business decision models / aggregates

Like mentioned in the [Background](/docs/background.md) section, _Gember Event Sourcing_ lets you model both **business decision models** using DCB and traditional **aggregate root models**. 

The setup for both are pretty much the same; they just need to implement the `EventSourcedDomainContext` interface.
A trait `EventSourcedDomainContextBehaviorTrait` is available for all required interface logic.

```php
final class SomeBusinessDecisionModel implements EventSourcedDomainContext
{
    use EventSourcedDomainContextBehaviorTrait;
    
    // Do your magic
}
```

When using DCB, each model is built from a specific stream of events tied to a set of **domain identifiers**. 

To make this work behind the scenes, the model needs to define which domain identifiers it is connected to. 
This can be done with the `#[DomainId]` attribute on one or more (private) properties. 
_Gember Event Sourcing_ will then load all events that are linked to **at least one** of those domain identifiers.

> Note: For a traditional aggregate root model, this is always just **one** domain identifier.

```php
final class SomeBusinessDecisionModel implements EventSourcedDomainContext
{
    use EventSourcedDomainContextBehaviorTrait;
    
    #[DomainId]
    private SomeId $someId;
    
    #[DomainId]
    private AnotherId $anotherId;
    
    // Do your magic
}
```

Next up is to add behavior to the model; primarily done in the form of methods. 
These methods typically consists of three main steps:

1. Check for idempotency
2. Protect invariants (business rules)
3. Apply a domain event

```php
final class SomeBusinessDecisionModel implements EventSourcedDomainContext
{
    use EventSourcedDomainContextBehaviorTrait;
    
    #[DomainId]
    private SomeId $someId;
    
    #[DomainId]
    private AnotherId $anotherId;
    
    public static function open() : self
    {
        /* 
         * For the first event in a model, a static method like this is often used
         * instead of the constructor.
         *
         * With DCB, this is usually not needed; there might already be events 
         * tied to any of the domain identifiers that are defined in this model.
         */
        $model = new self();
        
        $model->apply(new ModelOpenedEvent(/*...*/));
        
        return $model;
    }
    
    public function close() : void 
    {
        // 1. Check for idempotency
        // ...
        
        // 2. Protect invariants
        // ...

        // 3. Apply a domain event
        $this->apply(new ModelClosedEvent(/*...*/));
    }
}
```

Lastly, in order to check for idempotency and protect invariants, the model needs maintain a domain state. 
This basically means that it needs to keep all data required to make these decisions.

Therefore, the model can define **event subscribers** with the `#[DomainEventSubscriber]` attribute. 
Any event subscribed in this way is automatically loaded from the event store when building the model.

> Note: The model doesn’t have to be the one that applied the event. It just needs to be related to at least one of the model's domain identifiers.

```php
final class SomeBusinessDecisionModel implements EventSourcedDomainContext
{
    use EventSourcedDomainContextBehaviorTrait;
    
    #[DomainId]
    private SomeId $someId;

    #[DomainId]
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
     * That’s not needed here. The event type itself is used. The method name can be anything.
     */
    #[DomainEventSubscriber]
    private function applyModelArchivedEvent(ModelArchivedEvent $event) : void
    {
        // Update state  
    }
}
```

#### Examples

A simple example of a business decision model using several domain identifiers and events:

```php
use Gember\EventSourcing\DomainContext\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\DomainContext\Attribute\DomainId;
use Gember\EventSourcing\DomainContext\EventSourcedDomainContext;
use Gember\EventSourcing\DomainContext\EventSourcedDomainContextBehaviorTrait;

final class SubscribeStudentToCourse implements EventSourcedDomainContext
{
    use EventSourcedDomainContextBehaviorTrait;

    #[DomainId]
    private CourseId $courseId;
    #[DomainId]
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
use Gember\EventSourcing\DomainContext\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\DomainContext\Attribute\DomainId;
use Gember\EventSourcing\DomainContext\EventSourcedDomainContext;
use Gember\EventSourcing\DomainContext\EventSourcedDomainContextBehaviorTrait;

final class Course implements EventSourcedDomainContext
{
    use EventSourcedDomainContextBehaviorTrait;

    #[DomainId]
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

### Domain events
A simple example of a domain event related to multiple domain identifiers:

```php
#[DomainEvent(name: 'student-subscribed-to-course')]
final readonly class StudentSubscribedToCourseEvent
{
    public function __construct(
        #[DomainId]
        public string $courseId,
        #[DomainId]
        public string $studentId,
    ) {}
}
```

### Command handlers
A simple example of a command handler calling a business decision model:

```php
use Gember\EventSourcing\Repository\DomainContextRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;;

final readonly class SubscribeStudentToCourseHandler
{
    public function __construct(
        private DomainContextRepository $repository,
    ) {}

    #[AsMessageHandler(bus: 'command.bus')]
    public function __invoke(SubscribeStudentToCourseCommand $command): void
    {
        $context = $this->repository->get(
            SubscribeStudentToCourse::class,
            new CourseId($command->courseId),
            new StudentId($command->studentId),
        );

        $context->subscribe();

        $this->repository->save($context);
    }
}
```

For more extended example check out the demo application [gember/example-event-sourcing-dcb](https://github.com/GemberPHP/example-event-sourcing-dcb).
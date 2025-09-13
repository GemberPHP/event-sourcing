## Usage

### Use cases / aggregates

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

#### Command handlers
Typically, a behavioral action is being called from a command handler class. 
This is taken care of in your application itself, and not part of this library.

However, these command handlers often do not contain any extra value other than: (a) get use case, (b) trigger action, (c) save.
That makes a command handler in the original setup often redundant.

Therefore, this library introduces a `#[DomainCommandHandler]` attribute, to directly bind a command to a behavioral action method.
Still, the classic separate command handler setup is also possible.

When using this attribute, your application is responsible for registering each command to a generic command handler,
`UseCaseCommandHandler`. When using any of the provided libraries [gember/event-sourcing-symfony-bundle](https://github.com/GemberPHP/event-sourcing-symfony-bundle) or [gember/event-sourcing-universal-service-provider](https://github.com/GemberPHP/event-sourcing-universal-service-provider), it will take care of this for you. 

In the example below, both options:
_(normally one option is picked for one use case)_

```php
final class SomeBusinessUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;
    
    #[DomainTag]
    private SomeId $someId;
    
    #[DomainTag]
    private AnotherId $anotherId;
    
    // Behavioral method to be called from a separate command handler
    public static function open() : self
    {
        /* 
         * For the first event in a model, a static method like this is often used
         * instead of the constructor.
         *
         * With DCB, this is usually not needed; there might already be events 
         * tied to any of the domain tags that are defined in this model.
         */
        $model = new self();
        
        $model->apply(new ModelOpenedEvent(/*...*/));
        
        return $model;
    }
    
    /*
     * Behavioral method bind to a command.
     * With a CreationPolicy it can be configured whether a use case should be created when not found yet.
     * 
     * Note: When using the attribute, the method should NOT be static, NOR return anything (return void)
     */
    #[DomainCommandHandler(policy: CreationPolicy::Never)]
    public function close(CloseModelCommand $command) : void 
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

When chosen for a classic separate command handler, it could look like this:

```php
use Gember\EventSourcing\Repository\UseCaseRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;;

final readonly class SubscribeStudentToCourseHandler
{
    public function __construct(
        private UseCaseRepository $repository,
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

Lastly, in order to check for idempotency and protect invariants, the model needs maintain a domain state. 
This basically means that it needs to keep all data required to make these decisions.

Therefore, the model can define **event subscribers** with the `#[DomainEventSubscriber]` attribute. 
Any event subscribed in this way is automatically loaded from the event store when building the model.

> Note: The model doesn’t have to be the one that applied the event. It just needs to be related to at least one of the model's domain tags.
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

### Domain events

A domain event is a simple Data Transfer Object (or message), no business logic involved.
It typically describes something that already happened.

In _Gember Event Sourcing_, any DTO can be used as a domain event.

With DCB, a domain event is not limited to a single aggregate root model anymore. 
Instead, the same event can be used across multiple business decision models. 
To make this work, a domain event is linked to a set of **domain tags**.
You can define those by adding the `#[DomainTag]` attribute to the appropriate properties.

```php
final readonly class SomeEvent
{
    public function __construct(
        #[DomainTag]
        public string $id,
        public int $value,
    ) {}
}
```

In some cases, using this attribute isn’t enough. 
If you need more control, the event can implement the `SpecifiedDomainTags` interface instead.

```php
final readonly class SomeEvent implements SpecifiedDomainTags
{
    public function __construct(
        public string $id,
        public int $value,
    ) {}
    
    public function getDomainTags(): array
    {
        return [
            $this->id,
        ];
    }
}
```
Every domain event needs two things to be appended in the event store:

1. The event name
2. The event payload; the event needs to be serializable

#### Domain event name
There are a few ways to set the name of a domain event:

1. Use `#[DomainEvent]` attribute
2. Implement the `NamedDomainEvent` interface

If neither of those is used, _Gember Event Sourcing_ will fall back to generating a name based on the event’s FQCN (Fully Qualified Class Name)

```php
#[DomainEvent(name: 'some.event')]
final readonly class SomeEvent
{
    public function __construct(
        #[DomainTag]
        public string $id,
        public int $value,
    ) {}
}

final readonly class AnotherEvent implements NamedDomainEvent
{
    public function __construct(
        #[DomainTag]
        public string $id,
        public int $value,
    ) {}
    
    public static function getName(): string
    {
        return 'another.event';
    }
}
```

#### Serialization

_Gember Event Sourcing_ includes a `Serializer` that can automatically handle serialization for domain events.
Therefore, it is important to make sure all properties of a domain event are serializable.
It's common practice to only use primitive types for all properties. 

> Note: It can be tempting to use your domain Value Objects in your domain events, but that is usually not a good idea.
> Not only serialization can become difficult, but more importantly, using mutable Value Objects could lead to past events accidentally changing when the Value Objects evolves over time.
> That goes against the core idea of event sourcing, where events should be immutable once stored. 

If you need to handle more complex serialization, you can implement the `Serializable` interface
and take full control over how the event is serialized and deserialized.

```php
/**
 * @implements Serializable<array{
 *    id: string,
 *    value: int 
 * }, SomeEvent>
 */
#[DomainEvent(name: 'some.event')]
final readonly class SomeEvent implements Serializable
{
    public function __construct(
        #[DomainTag]
        public string $id,
        public int $value,
    ) {}
    
    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['id'],
            $payload['value'],
        );
    }

    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
        ];
    }
}
```

#### Examples

A simple example of a domain event related to multiple domain tags:

```php
#[DomainEvent(name: 'student-subscribed-to-course')]
final readonly class StudentSubscribedToCourseEvent
{
    public function __construct(
        #[DomainTag]
        public string $courseId,
        #[DomainTag]
        public string $studentId,
    ) {}
}
```

For more extended example check out the demo application [gember/example-event-sourcing-dcb](https://github.com/GemberPHP/example-event-sourcing-dcb).
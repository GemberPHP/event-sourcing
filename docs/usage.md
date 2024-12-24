## Usage

### Business decision models / aggregates

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

A simple example of a 'traditional' aggregate root:

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
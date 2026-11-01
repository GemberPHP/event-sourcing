## Example

This example demonstrates all key features of the library: a DCB use case with command handler, domain events, and a saga coordinating a workflow.

**Scenario**: A student subscribes to a course. When a student subscribes for the first time, a saga sends them a welcome email, once.

### Domain Events

```php
use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\Saga\Attribute\SagaId;

#[DomainEvent(name: 'course.created')]
final readonly class CourseCreatedEvent
{
    public function __construct(
        #[DomainTag]
        public string $courseId,
        public string $name,
    ) {}
}

#[DomainEvent(name: 'student.registered')]
final readonly class StudentRegisteredEvent
{
    public function __construct(
        #[DomainTag]
        public string $studentId,
        public string $email,
    ) {}
}

#[DomainEvent(name: 'student.subscribed')]
final readonly class StudentSubscribedEvent
{
    public function __construct(
        #[DomainTag]
        public string $courseId,
        #[DomainTag]
        #[SagaId]  // Links to the student's StudentWelcomeSaga
        public string $studentId,
    ) {}
}
```

### Use Case with Command Handler

```php
use DomainException;
use Gember\EventSourcing\UseCase\Attribute\DomainCommandHandler;
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

    private bool $isCourseCreated = false;
    private bool $isStudentRegistered = false;
    private bool $isSubscribed = false;

    /**
     * Subscribes a student to a course (DCB pattern with multiple domain tags).
     * Uses __invoke to emphasize this is a single-purpose use case.
     */
    #[DomainCommandHandler]
    public function __invoke(SubscribeStudentCommand $command): void
    {
        // 1. Protect invariants (simplified for example)
        // In real scenarios: check capacity, prerequisites, etc.
        if (!$this->isCourseCreated || !$this->isStudentRegistered) {
            throw new DomainException('Unknown course or student');
        }

        // 2. Check idempotency
        if ($this->isSubscribed) {
            return;
        }

        // 3. Apply domain event
        $this->apply(new StudentSubscribedEvent(
            $command->courseId,
            $command->studentId,
        ));
    }

    #[DomainEventSubscriber]
    private function onCourseCreated(CourseCreatedEvent $event): void
    {
        $this->courseId = new CourseId($event->courseId);
        $this->isCourseCreated = true;
    }

    #[DomainEventSubscriber]
    private function onStudentRegistered(StudentRegisteredEvent $event): void
    {
        $this->studentId = new StudentId($event->studentId);
        $this->isStudentRegistered = true;
    }

    #[DomainEventSubscriber]
    private function onStudentSubscribed(StudentSubscribedEvent $event): void
    {
        // Events matching any of the domain tags are loaded: also other students'
        // subscriptions to this course, and this student's other subscriptions
        if ($event->courseId === (string) $this->courseId && $event->studentId === (string) $this->studentId) {
            $this->isSubscribed = true;
        }
    }
}
```

### Saga

```php
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Saga\Attribute\SagaEventSubscriber;
use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\Saga\CommandRecorder;

#[Saga(name: 'student.welcome')]
final class StudentWelcomeSaga
{
    #[SagaId]
    public ?string $studentId = null;

    // Saga state is persisted: keep it in public properties (see Sagas - Serialization)
    public bool $welcomeEmailSent = false;

    /**
     * On the student's first subscription, send a welcome email (once per student).
     */
    #[SagaEventSubscriber(policy: CreationPolicy::IfMissing)]
    public function onStudentSubscribed(StudentSubscribedEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->studentId = $event->studentId;

        if ($this->welcomeEmailSent) {
            return;
        }

        // Record command to send welcome email (dispatched after saga is persisted)
        $commandRecorder->record(new SendWelcomeEmailCommand(
            $event->studentId,
            $event->courseId,
        ));

        $this->welcomeEmailSent = true;
    }
}
```

For more extended examples and complete implementations, check out the demo application [gember/example-event-sourcing-dcb](https://github.com/GemberPHP/example-event-sourcing-dcb).

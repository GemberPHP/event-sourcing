## Command handlers

Command handlers are the bridge between your application layer (commands) and your domain layer (use cases/aggregates). They are responsible for loading the use case, triggering the behavioral method, and persisting the changes.

### Overview

In traditional setups, each command requires a dedicated handler class. However, these handlers often contain repetitive boilerplate code:
1. Get the use case from the repository
2. Trigger the behavioral action
3. Save the use case back to the repository

_Gember Event Sourcing_ offers two approaches to handle commands:

1. **Attribute-based approach** - Use the `#[DomainCommandHandler]` attribute to bind commands directly to use case methods
2. **Classic approach** - Write dedicated command handler classes (useful for complex scenarios)

Both approaches are valid, and you can mix them in your application based on your needs.

### Attribute-based command handlers

The `#[DomainCommandHandler]` attribute lets you bind a command directly to a use case method, eliminating the need for a separate handler class.

#### Requirements for handler methods

When using the `#[DomainCommandHandler]` attribute, the method must follow these rules:

- **Must not be static** - The method needs access to `$this` to call `apply()` and other instance methods
- **Must return void** - Return values are not used; all state changes should be made via `apply()`
- **Must have exactly one parameter** - The parameter must have a type hint representing the command class

#### CreationPolicy

The attribute accepts a `policy` parameter that controls what happens when the use case doesn't exist yet:

- **`CreationPolicy::Never`** (default) - Throws an exception if the use case is not found
- **`CreationPolicy::IfMissing`** - Creates a new use case instance if not found

```php
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\UseCase\Attribute\DomainCommandHandler;

#[DomainCommandHandler(policy: CreationPolicy::Never)]
public function close(CloseModelCommand $command): void
{
    // Method implementation
}
```

#### How it works

When you use the `#[DomainCommandHandler]` attribute:

1. The library automatically discovers methods with this attribute during registration
2. Each command class is mapped to its corresponding use case and method
3. When a command is dispatched, the generic `UseCaseCommandHandler` handles it by:
   - Extracting domain tags from the command
   - Loading (or creating) the use case from the repository
   - Invoking the handler method
   - Saving the use case back to the repository

#### Framework integration

When using [gember/event-sourcing-symfony-bundle](https://github.com/GemberPHP/event-sourcing-symfony-bundle) or [gember/event-sourcing-universal-service-provider](https://github.com/GemberPHP/event-sourcing-universal-service-provider), all command registrations are handled automatically. Without these, you need to manually register each command to the `UseCaseCommandHandler`.

### Example: Attribute-based handler

This example shows a use case with a command handler method using the `#[DomainCommandHandler]` attribute:

```php
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\UseCase\Attribute\DomainCommandHandler;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;

final class PublishCourseUseCase implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private CourseId $courseId;

    private string $name;
    private bool $isPublished = false;

    /**
     * Publishes the course, making it available to students.
     * Uses CreationPolicy::Never - the course must already exist.
     */
    #[DomainCommandHandler(policy: CreationPolicy::Never)]
    public function __invoke(PublishCourseCommand $command): void
    {
        // 1. Check for idempotency
        if ($this->isPublished) {
            return;
        }

        // 2. Protect invariants (business rules)
        if (empty($this->name)) {
            throw new \DomainException('Cannot publish a course without a name');
        }

        // 3. Apply domain event
        $this->apply(new CoursePublishedEvent((string) $this->courseId));
    }


    #[DomainEventSubscriber]
    private function onCoursePublishedEvent(CoursePublishedEvent $event): void
    {
        $this->isPublished = true;
    }
}
```

**Key points:**
- Each command handler method is bound to a specific command via its type-hinted parameter
- The method body follows the standard three-step pattern: check idempotency, protect invariants, apply event
- Different methods can have different creation policies based on business requirements
- Domain tag values are automatically extracted from the command and used to load the use case

### Classic command handlers

For more complex scenarios or when you need more control, you can write dedicated command handler classes. This approach gives you full flexibility to inject additional dependencies, perform complex validations, or coordinate multiple use cases.

#### Using UseCaseRepository

The `UseCaseRepository` is used to load and save use cases:

- **`get(string $useCaseClassName, string|Stringable ...$domainTag)`** - Loads a use case by class name and domain tags
- **`save(EventSourcedUseCase $useCase)`** - Persists all applied events to the event store

Domain tags can be passed as strings or any objects implementing the `Stringable` interface (typically value objects like `CourseId` or `StudentId`).

### Example: Classic command handler

This example shows a dedicated command handler class with dependency injection and explicit control over the flow:

```php
use Gember\EventSourcing\Repository\UseCaseRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class SubscribeStudentToCourseHandler
{
    public function __construct(
        private UseCaseRepository $repository,
        private CourseCapacityChecker $capacityChecker,
        private StudentEligibilityService $eligibilityService,
    ) {}

    #[AsMessageHandler(bus: 'command.bus')]
    public function __invoke(SubscribeStudentToCourseCommand $command): void
    {
        // Load the use case with domain tags
        $subscription = $this->repository->get(
            SubscribeStudentToCourse::class,
            new CourseId($command->courseId),
            new StudentId($command->studentId),
        );

        // Trigger the behavioral method
        $subscription->subscribe();

        // Persist the changes
        $this->repository->save($subscription);
    }
}
```

**Key points:**
- You have full control over the handler's dependencies
- You explicitly load the use case from the repository using domain tags
- You can perform additional checks or orchestration before/after calling the use case method
- You must manually call `repository->save()` to persist changes

### Choosing between approaches

Both approaches have their place in your application. Here's when to use each:

#### Use attribute-based handlers when:
- The handler logic is straightforward (load, execute, save)
- No additional dependencies are needed beyond the use case itself
- You want to reduce boilerplate code
- The command maps cleanly to a single use case method

#### Use classic handlers when:
- You need to inject additional services (validators, external APIs, etc.)
- Complex pre/post processing is required
- You need to coordinate multiple use cases
- You want explicit control over the execution flow
- You're performing integration with external systems
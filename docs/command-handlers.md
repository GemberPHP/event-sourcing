## Command handlers

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

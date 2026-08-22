## Observability

_Gember Event Sourcing_ provides built-in logging through decorator classes that wrap the core components. These decorators use PSR-3 for logging, making them compatible with any framework.

### Logging

Three loggable decorators provide structured logging for the main execution paths:

| Decorator | Wraps | Logs |
|-----------|-------|------|
| `LoggableEventStoreDecorator` | `EventStore` | Event loading and appending |
| `LoggableUseCaseCommandExecutorDecorator` | `UseCaseCommandExecutor` | Command handling on use cases |
| `LoggableSagaEventExecutorDecorator` | `SagaEventExecutor` | Event handling on sagas |

All decorators log at `INFO` level using PSR-3's `LoggerInterface`.

### What gets logged

Each decorator logs the start, completion, and failure of its wrapped operation:

**Event store (load/append):**
```
[EventStore] Started loading events          {domainTags: [...]}
[EventStore] Loaded event CourseCreatedEvent  {eventId: "...", appliedAt: "...", metadata: {...}, domainTags: [...]}
[EventStore] Ended loading events            {domainTags: [...], duration: 0.0042}
```

**Command handling:**
```
[UseCase] Started handling CreateCourseCommand by CreateCourse  {domainTags: [...]}
[UseCase] Finished handling CreateCourseCommand by CreateCourse {domainTags: [...], duration: 0.0051}
```

**Saga event handling:**
```
[Saga] Started handling OrderPlacedEvent by OrderFulfillmentSaga  {sagaId: "order-123"}
[Saga] Finished handling OrderPlacedEvent by OrderFulfillmentSaga {sagaId: "order-123", duration: 0.0023}
```

On failure, the log includes `exception` (message) and `exceptionClass` in the context. All durations are measured in seconds using `microtime(true)`.

### Symfony bundle configuration

When using the Symfony bundle, logging decorators are **enabled by default**. They automatically wrap the core components using Symfony's service decoration. No additional configuration is needed.

The logger service can be customized in `gember_event_sourcing.yaml`:

```yaml
gember_event_sourcing:
    logging:
        logger: '@logger'  # default
```

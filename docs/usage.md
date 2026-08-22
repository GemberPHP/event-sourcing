## Usage

This section covers the core concepts of _Gember Event Sourcing_ and how to use them in your application.

### Core concepts

These topics are listed in recommended reading order — each builds on the previous:

1. [Domain events](/docs/usage/domain-events.md) - Define and work with domain events, including naming, serialization, and domain tags
2. [Commands](/docs/usage/commands.md) - Define commands that carry intent and domain tags for event retrieval
3. [Use cases / aggregates](/docs/usage/use-cases.md) - Model business logic using event-sourced use cases and traditional aggregates with DCB or aggregate patterns
4. [Command handlers](/docs/usage/command-handlers.md) - Trigger behavioral actions on use cases using command handlers
5. [Sagas](/docs/usage/sagas.md) - Implement long-running business processes that coordinate complex workflows across multiple domain events

### Operational

- [Outbox](/docs/usage/outbox.md) - Ensure reliable delivery of domain events and saga commands using the transactional outbox pattern
- [Observability](/docs/usage/observability.md) - Structured logging for event store, command handling, and saga execution
- [Caching](/docs/usage/caching.md) - Cache resolver and registry metadata to avoid runtime reflection overhead

### Related resources
- For more extended examples and complete implementations, check out the demo application [gember/example-event-sourcing-dcb](https://github.com/GemberPHP/example-event-sourcing-dcb)

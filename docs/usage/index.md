## Usage

This section covers the core concepts of _Gember Event Sourcing_ and how to use them in your application.

The typical flow is: define **domain events** that describe what happens in your domain, create **commands** that carry the intent to trigger changes, build **use cases** that protect business rules and apply events, and wire them together with **command handlers**. For cross-boundary workflows, add **sagas**.

### Core concepts

These topics are listed in recommended reading order — each builds on the previous:

1. [Domain events](domain-events.md) - Define and work with domain events, including naming, serialization, and domain tags
2. [Commands](commands.md) - Define commands that carry intent and domain tags for event retrieval
3. [Use cases / aggregates](use-cases.md) - Model business logic using event-sourced use cases and traditional aggregates with DCB or aggregate patterns
4. [Command handlers](command-handlers.md) - Trigger behavioral actions on use cases using command handlers
5. [Sagas](sagas.md) - Implement long-running business processes that coordinate complex workflows across multiple domain events

### Operational

- [Snapshotting](snapshotting.md) - Optimize reconstitution performance by capturing point-in-time use case state
- [Outbox](outbox.md) - Ensure reliable delivery of domain events and saga commands using the transactional outbox pattern
- [Observability](observability.md) - Structured logging for event store, command handling, and saga execution
- [Caching](caching.md) - Cache resolver and registry metadata to avoid runtime reflection overhead

### Related resources
- For more extended examples and complete implementations, check out the demo application [gember/example-event-sourcing-dcb](https://github.com/GemberPHP/example-event-sourcing-dcb)

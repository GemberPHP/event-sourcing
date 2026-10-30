## Outbox

The **transactional outbox** pattern ensures reliable delivery of domain events and saga commands. Instead of dispatching messages inline (which can lose data if the process crashes between persisting and dispatching), messages are written to an outbox table atomically with the state change and dispatched later by a background processor.

### The problem

Without the outbox, there are two data loss points:

1. **Event dispatching** — After events are persisted to the event store, they are dispatched synchronously via the event bus. If the process crashes between persist and dispatch, events are stored but never reach sagas or projections.

2. **Command dispatching** — After saga state is saved, recorded commands are flushed via the command bus. If the process crashes between save and flush, the saga has transitioned state but its side-effect commands are lost.

### How the outbox solves this

The outbox introduces three mechanisms:

1. **Outbox bus implementations** — `OutboxEventBus` and `OutboxCommandBus` implement the standard `EventBus` and `CommandBus` interfaces, but write messages to the outbox table instead of dispatching them. They are swapped in via dependency injection — no changes to existing application code.

2. **Transactional decorators** — `TransactionalUseCaseRepositoryDecorator` wraps the use case repository's `save()` in a database transaction, ensuring the event store append and the outbox writes commit atomically. `TransactionalSagaEventExecutorDecorator` does the same for saga state persistence and command outbox writes. These decorators can also be used independently of the outbox — for example, to wrap event store writes and event bus dispatching in a single transaction without using the outbox pattern.

3. **Outbox processor** — A background worker that polls the outbox table, dispatches messages via the real event bus and command bus, and marks them as processed.

### Transaction flow

When a use case is saved with the outbox enabled:

```
TransactionalUseCaseRepositoryDecorator.save()
  └── database transaction {
        ├── eventStore.append()             ← nested transaction (savepoint)
        ├── outboxEventBus.handle(event1)   ← writes to outbox table
        └── outboxEventBus.handle(event2)   ← writes to outbox table
      }                                     ← single atomic commit
```

If anything fails, the entire transaction rolls back — both the event store writes and the outbox entries. No partial state.

The same pattern applies to saga command dispatching:

```
RetryingSagaEventExecutorDecorator.execute()  ← retries the whole transaction on a concurrent saga change
  └── TransactionalSagaEventExecutorDecorator.execute()
        └── database transaction {
              ├── sagaStore.save()          ← saga state persisted (optimistic revision check)
              └── commandRecorder.flush()   ← OutboxCommandBus writes commands to outbox
            }                               ← single atomic commit
```

The retrying decorator is the outermost one, so each attempt runs in a transaction of its own (see [How it works - Saga concurrency](../how-it-works.md#saga-concurrency)).

### Enabling the outbox (Symfony)
> **Note:** This section applies to the Symfony bundle package [event-sourcing-symfony-bundle](https://github.com/GemberPHP/event-sourcing-symfony-bundle)

Configure the dispatch strategy in your bundle configuration:

```yaml
gember_event_sourcing:
    dispatch:
        strategy: outbox    # 'direct' (default) or 'outbox'
        max_retries: 5      # default: 5, minimum: 1
```

Setting `strategy: outbox` automatically:
- Replaces the event bus and command bus with outbox variants
- Wraps the use case repository and saga event executor in transactional decorators
- Registers the outbox processor and console command

No changes to your domain code, commands, events, use cases, or sagas are needed.

### Running the outbox processor
> **Note:** This section applies to the Symfony bundle package [event-sourcing-symfony-bundle](https://github.com/GemberPHP/event-sourcing-symfony-bundle)

The outbox processor is a console command that polls the outbox table and dispatches pending messages:

```bash
# Single run (e.g., for cron)
bin/console gember:outbox:process

# Continuous polling (for supervisor/systemd)
bin/console gember:outbox:process --watch

# With production-recommended options
bin/console gember:outbox:process --watch --limit=100 --memory-limit=128M --time-limit=3600
```

#### Options

| Option | Description | Default |
|--------|-------------|---------|
| `--limit`, `-l` | Max messages to process per batch | `100` |
| `--watch`, `-w` | Continuously poll with interval in milliseconds | disabled |
| `--memory-limit` | Exit when memory usage exceeds this limit (e.g., `128M`) | unlimited |
| `--time-limit` | Exit after this many seconds | unlimited |

The command handles `SIGTERM` and `SIGINT` gracefully — it finishes the current batch before exiting. Use a process manager like supervisor or systemd to restart it automatically:

```ini
# /etc/supervisor/conf.d/gember-outbox.conf
[program:gember-outbox]
command=bin/console gember:outbox:process --watch --memory-limit=128M --time-limit=3600
autostart=true
autorestart=true
```

### Outbox table

The outbox uses a single table for both events and commands:

| Column | Type | Description |
|--------|------|-------------|
| `id` | varchar(50) PK | Unique message ID |
| `message_type` | varchar(20) | `'event'` or `'command'` |
| `message_name` | varchar(255) | FQCN of the event/command class |
| `payload` | json | Serialized message |
| `created_at` | timestamp(6) | When the message was written |
| `retry_count` | int, default 0 | Number of failed dispatch attempts |
| `processed_at` | timestamp(6), nullable | Set when successfully dispatched |
| `dead_lettered_at` | timestamp(6), nullable | Set when `retry_count` exceeds `max_retries` |
| `claimed_by` | varchar(100), nullable | The processor that claimed the message (`host:pid:random`) |
| `claimed_until` | timestamp(6), nullable | Until when the claim holds (its lease) |

The table is created by a migration shipped with the [rdbms-event-store-doctrine-dbal](https://github.com/GemberPHP/rdbms-event-store-doctrine-dbal) package (both Doctrine Migrations and Phinx migrations are provided).

### Failure handling

When a message fails to dispatch, the processor:

1. Logs a warning with the message ID, type, class name, and exception details
2. Increments `retry_count` on the outbox row
3. Continues processing the remaining messages in the batch

The message will be retried on the next processor run. When `retry_count` exceeds `max_retries`, the `dead_lettered_at` timestamp is set and the message is permanently excluded from processing.

#### Inspecting dead-lettered messages

Dead-lettered messages remain in the outbox table for manual inspection:

```sql
SELECT * FROM outbox
WHERE dead_lettered_at IS NOT NULL;
```

After investigating and fixing the root cause, you can reset a message for reprocessing:

```sql
UPDATE outbox
SET dead_lettered_at = NULL, retry_count = 0
WHERE id = 'message-id';
```

### Delivery guarantees

The outbox provides **at-least-once delivery**. This means:

- Every message will be dispatched at least once
- In rare failure scenarios, a message may be dispatched more than once: a processor crashing after dispatch but before marking the message as processed, or a batch taking longer to dispatch than the lease (see below)
- Event handlers and command handlers **must be idempotent** — processing the same message twice should produce the same result

### Concurrent processors

Multiple processor instances can run in parallel, without coordination. Each processor **claims** the batch of messages it takes: in a short transaction, the messages are marked with the processor (`claimed_by`) and a lease (`claimed_until`, 300 seconds by default). Other processors skip claimed messages, so every message is dispatched by one processor at a time. Dispatching happens after the claim is committed, outside any transaction.

- **Lease:** a claim expires after the lease. When a processor crashes or hangs, other processors take its messages over after that time. The lease must exceed the time to dispatch one batch (`--limit`, 100 by default): otherwise other processors take over the rest of a batch that is still being dispatched, and dispatch those messages again. Lower `--limit` for slow handlers. The lease is a constructor argument of `DoctrineDbalRdbmsOutboxRepository` (`$leaseSeconds`).
- **Failures:** a failed dispatch is counted only by the processor holding the claim, which releases the message for a retry.
- **No ordering across processors:** with one processor, messages are dispatched oldest first (apart from retries). With several processors, messages are dispatched in parallel, so two events for the same use case or saga can be handled at the same time and in any order. Use several processors when your handlers don't depend on the order of events; keep one processor when they do.
- **Databases without `SKIP LOCKED`** (SQLite, MySQL 5.7, MariaDB < 10.6): claiming stays correct, but concurrent processors compete for the same messages more often, so adding processors helps less.

!!! note "Upgrading to claimed outbox messages"
    Before `gember/rdbms-event-store-doctrine-dbal` 0.21.0, concurrent processors dispatched the same messages (the `SKIP LOCKED` locks were released right away). The released migrations were changed in place, so a database that already ran them needs the two columns added manually:

    ```sql
    ALTER TABLE outbox
      ADD COLUMN claimed_by varchar(100) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
      ADD COLUMN claimed_until timestamp(6) NULL DEFAULT NULL;
    ```

    This is instant on MySQL 8 and MariaDB 10.3+ (no table rebuild). The previous version ignores the new columns, so add them before deploying; a rolling deploy works. Stop processors of the previous version before starting new ones: running side by side, the old ones still dispatch messages the new ones claimed. SQLite: two `ALTER TABLE outbox ADD COLUMN …` statements.

### Architecture

The outbox is implemented as a composable layer on top of the existing architecture:

```
┌─────────────────────────────────────────────────────┐
│ TransactionalUseCaseRepositoryDecorator             │
│   └── EventSourcedUseCaseRepository                 │
│         ├── EventStore.append()                     │
│         └── OutboxEventBus.handle() → outbox table  │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ RetryingSagaEventExecutorDecorator                  │
│   └── TransactionalSagaEventExecutorDecorator       │
│         └── DefaultSagaEventExecutor                │
│               ├── SagaStore.save()                  │
│               └── CommandRecorder.flush()           │
│                     └── OutboxCommandBus.handle()   │
│                           → outbox table            │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│ OutboxProcessor (background worker)                 │
│   ├── OutboxStore.getUnprocessedMessages()          │
│   │     → claims a batch (short transaction)        │
│   ├── EventBus.handle() / CommandBus.handle()       │
│   │     → outside any transaction                   │
│   └── OutboxStore.markAsProcessed() / markAsFailed()│
└─────────────────────────────────────────────────────┘
```

No existing classes are modified. The outbox is activated purely through dependency injection wiring in the bundle.

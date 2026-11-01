## Outbox

The **transactional outbox** pattern ensures reliable delivery of domain events and saga commands. Instead of dispatching messages inline (which can lose data if the process crashes between persisting and dispatching), messages are written to an outbox table atomically with the state change and dispatched later by a background processor.

### The problem

Without the outbox, there are two data loss points:

1. **Event dispatching** — After events are persisted to the event store, they are dispatched synchronously via the event bus. If the process crashes between persist and dispatch, events are stored but never reach sagas or projections.

2. **Command dispatching** — After saga state is saved, recorded commands are flushed via the command bus. If the process crashes between save and flush, the saga has transitioned state but its side-effect commands are lost.

### How the outbox solves this

The outbox introduces three mechanisms:

1. **Outbox bus implementations** — `OutboxEventBus` and `OutboxCommandBus` implement the standard `EventBus` and `CommandBus` interfaces, but write messages to the outbox table instead of dispatching them. They are swapped in via dependency injection — no changes to existing application code.

2. **Transactional decorators** — `TransactionalUseCaseRepositoryDecorator` wraps the use case repository's `save()` in a database transaction, ensuring the event store append and the outbox writes commit atomically. `TransactionalSagaEventExecutorDecorator` does the same for saga state persistence and command outbox writes.

3. **Outbox processor** — A background worker that polls the outbox table, dispatches messages via the real event bus and command bus, and marks them as processed.

### Transaction flow

When a use case is saved with the outbox enabled:

```
TransactionalUseCaseRepositoryDecorator.save()
  └── database transaction {
        ├── eventStore.append()             ← runs inside this transaction
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
        max_retries: 5      # retries after the first attempt; default: 5, minimum: 1
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
| `--watch`, `-w` | Continuously poll, waiting this many milliseconds after an empty batch (`--watch=500`; minimum 10) | disabled; `100` when given without a value |
| `--memory-limit` | With `--watch`: exit when memory usage exceeds this limit (e.g., `128M`) | unlimited |
| `--time-limit` | With `--watch`: exit after this many seconds | unlimited |

The command handles `SIGTERM` and `SIGINT` gracefully (via `ext-pcntl`) — it finishes the current batch before exiting. Use a process manager like supervisor or systemd to restart it automatically, and give it enough time to finish a batch when stopping: a killed batch is only dispatched again after its lease (5 minutes by default).

```ini
# /etc/supervisor/conf.d/gember-outbox.conf
[program:gember-outbox]
command=/path/to/project/bin/console gember:outbox:process --watch --memory-limit=128M --time-limit=3600
autostart=true
autorestart=true
stopwaitsecs=60
```

#### Without Symfony

With the universal service provider (`dispatch.strategy: outbox`), there is no console command: run the processor in your own loop, cron job or worker, outside any transaction:

```php
use Gember\EventSourcing\Outbox\Processor\OutboxProcessor;

$processor = $container->get(OutboxProcessor::class);

while (true) {
    $processed = $processor->process(limit: 100); // returns the number of messages handled

    if ($processed === 0) {
        usleep(100_000);
    }
}
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
| `dead_lettered_at` | timestamp(6), nullable | Set when `retry_count` exceeds `max_retries`, or right away for a message that can't be deserialized |
| `claimed_by` | varchar(100), nullable | The processor that claimed the message (`host:pid:random`) |
| `claimed_until` | datetime(6), nullable | In UTC by the database clock: until when the claim holds (its lease) or, for a failed message (`claimed_by` empty), when it's retried |

The table is created by a migration shipped with the [rdbms-event-store-doctrine-dbal](https://github.com/GemberPHP/rdbms-event-store-doctrine-dbal) package (both Doctrine Migrations and Phinx migrations are provided). When upgrading from an earlier version, see [Upgrading](../upgrading.md).

### Failure handling

When a message fails to dispatch, the processor:

1. Logs a warning with the message ID, type, class name, and exception details
2. Increments `retry_count` on the outbox row and schedules a retry after a backoff (in `claimed_until`)
3. Continues processing the remaining messages in the batch

The backoff starts at 5 seconds and doubles per failed attempt, up to 5 minutes (the `$retryDelaySeconds` and `$maxRetryDelaySeconds` arguments of `DoctrineDbalRdbmsOutboxRepository`), so a short outage of a dependency doesn't use up the retries. After the first attempt and `max_retries` retries (`retry_count` exceeds `max_retries`), the `dead_lettered_at` timestamp is set and the message is permanently excluded from processing. How long a message is retried:

| `max_retries` | Retried for |
|---|---|
| 1 | 5 s |
| 3 | 35 s |
| 5 | ~2.5 min |
| 7 | ~10 min |
| 10 | ~25 min |

From the 7th retry on, each retry waits 5 minutes. An outage that lasts longer still ends in dead-lettered messages: reset them after the outage (see below). The last failure is logged like the others, so alert on dead-lettered messages (`dead_lettered_at`) rather than on the log.

Retried messages are dispatched after messages that were written later: messages aren't dispatched in order after a failure. Event handlers shouldn't depend on the order of events across failures.

A message that can't be deserialized (e.g. its class was renamed or removed) can't succeed on a retry: it's dead-lettered right away, logged as an error, and the other messages of its batch are dispatched as usual.

#### Inspecting dead-lettered messages

Dead-lettered messages remain in the outbox table for manual inspection:

```sql
SELECT * FROM outbox
WHERE processed_at IS NULL AND dead_lettered_at IS NOT NULL;
```

After investigating and fixing the root cause, you can reset a message for reprocessing:

```sql
UPDATE outbox
SET dead_lettered_at = NULL, retry_count = 0, claimed_until = NULL
WHERE id = 'message-id';
```

#### Monitoring the backlog

```sql
-- Messages not dispatched yet, and the age of the oldest one
SELECT COUNT(*) AS pending, MIN(created_at) AS oldest
FROM outbox
WHERE processed_at IS NULL AND dead_lettered_at IS NULL;
```

#### Messages waiting for a retry

```sql
-- Messages waiting for a retry, and when they're retried (UTC)
SELECT id, message_name, retry_count, claimed_until
FROM outbox
WHERE processed_at IS NULL AND dead_lettered_at IS NULL AND claimed_by IS NULL AND claimed_until IS NOT NULL;

-- Retry them now (e.g. after an outage was resolved)
UPDATE outbox SET claimed_until = NULL
WHERE processed_at IS NULL AND dead_lettered_at IS NULL AND claimed_by IS NULL;
```

#### Removing processed messages

Processed messages stay in the outbox table; nothing deletes them. Remove them periodically, in batches, for example processed messages older than 7 days:

```sql
DELETE FROM outbox
WHERE processed_at IS NOT NULL AND processed_at < NOW() - INTERVAL 7 DAY
LIMIT 10000;
```

Repeat until no rows are deleted. Keep dead-lettered messages until you've handled them.

#### Changing the lease and backoff

The lease (`$leaseSeconds`, default 300) and the backoff (`$retryDelaySeconds`, default 5, and `$maxRetryDelaySeconds`, default 300) are constructor arguments of `DoctrineDbalRdbmsOutboxRepository`, not bundle options. With the Symfony bundle, override the service:

```yaml
# config/services.yaml
services:
    gember.event_sourcing.outbox.rdbms.rdbms_outbox_repository:
        class: Gember\RdbmsEventStoreDoctrineDbal\Outbox\DoctrineDbalRdbmsOutboxRepository
        arguments:
            $connection: '@gember.doctrine.dbal.connection'
            $tableSchema: '@gember.event_sourcing.outbox.table_schema.outbox_table_schema'
            $outboxFactory: '@gember.event_sourcing.outbox.doctrine_dbal_rdbms_outbox_factory'
            $leaseSeconds: 600
```

With the universal service provider, register your own `OutboxStore::class` entry: an `RdbmsOutboxStore` with a `DoctrineDbalRdbmsOutboxRepository` constructed with these arguments.

### Delivery guarantees

The outbox provides **at-least-once delivery**. This means:

- Every message will be dispatched at least once, unless it is dead-lettered
- In rare failure scenarios, a message may be dispatched more than once: a processor crashing after dispatch but before marking the message as processed, or a batch taking longer to dispatch than the lease (see below)
- Event handlers and command handlers **must be idempotent** — processing the same message twice should produce the same result

### Concurrent processors

Multiple processor instances can run in parallel, without coordination. Each processor **claims** the batch of messages it takes: the messages are marked with the processor (`claimed_by`) and a lease (`claimed_until`, 300 seconds by default). Other processors skip claimed messages, so every message is dispatched by one processor at a time. Dispatching happens after the claim is committed, outside any transaction: run the processor outside any transaction (not inside e.g. a `doctrine_transaction` middleware), otherwise the claim and its locks stay in your transaction while the batch is dispatched.

- **Lease:** a claim expires after the lease, measured in UTC with the database clock (all processors share it, whatever their PHP or database session time zone). When a processor crashes or hangs, other processors take its messages over after that time. The lease must exceed the time to dispatch one batch (`--limit`, 100 by default): otherwise other processors take over the rest of a batch that is still being dispatched, and dispatch those messages again. Lower `--limit` for slow handlers. The lease is a constructor argument of `DoctrineDbalRdbmsOutboxRepository` (`$leaseSeconds`, see [Changing the lease and backoff](#changing-the-lease-and-backoff)).
- **Failures:** a failed dispatch is counted only by the processor holding the claim, which releases the message for a retry after the backoff (see [Failure handling](#failure-handling)).
- **No ordering across processors:** with one processor, messages are dispatched oldest first (apart from retries after a failure). With several processors, messages are dispatched in parallel, so two events for the same use case or saga can be handled at the same time and in any order. Use several processors when your handlers don't depend on the order of events; keep one processor when they do.
- **Databases without `SKIP LOCKED`** (SQLite, MySQL 5.7, MariaDB < 10.6): claiming stays correct, but concurrent processors compete for the same messages more often, so adding processors helps less.

Inspect the claims of messages that aren't processed yet:

```sql
SELECT claimed_by, COUNT(*) AS messages, MAX(claimed_until) AS claimed_until
FROM outbox
WHERE processed_at IS NULL AND dead_lettered_at IS NULL AND claimed_by IS NOT NULL
GROUP BY claimed_by;
```

Claims expire by themselves. To hand over the messages of a processor that is known to be gone (e.g. killed in the middle of a batch) before its lease expires:

```sql
UPDATE outbox SET claimed_by = NULL, claimed_until = NULL
WHERE claimed_by = '<claimed_by>' AND processed_at IS NULL;
```

### Processor flow

```
OutboxProcessor (background worker)
  ├── OutboxStore.getUnprocessedMessages()   → claims a batch (short transaction)
  ├── EventBus.handle() / CommandBus.handle() → outside any transaction
  └── OutboxStore.markAsProcessed() / markAsFailed()
```

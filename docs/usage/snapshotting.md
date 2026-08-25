## Snapshotting

Event-sourced use cases reconstitute by replaying all events from the event store. For use cases with many events, this becomes slow. Snapshotting captures a point-in-time serialized state so reconstitution only needs to replay events since the last snapshot.

```
Without snapshot:
  Event Store: [E1] → [E2] → [E3] → ... → [E9999] → [E10000]
  Reconstitute: replay ALL 10,000 events

With snapshot:
  Snapshot Store: { state @ E9500, lastEventId: "E9500" }
  Event Store: [E9501] → ... → [E10000]
  Reconstitute: deserialize snapshot state → replay only 500 events
```

### Configuring snapshots

Snapshotting is configured per use case via the `#[Snapshot]` attribute. The attribute accepts three independent trigger conditions — any of them triggers a snapshot (OR logic).

```php
use Gember\EventSourcing\UseCase\Attribute\DomainEventSubscriber;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;
use Gember\EventSourcing\UseCase\Attribute\Snapshot;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\UseCase\EventSourcedUseCaseBehaviorTrait;
use Gember\EventSourcing\Util\Time\Duration;

#[Snapshot(
    afterEvents: 500,
    afterSourcingTime: new Duration(milliseconds: 500),
    onEvent: AccountClosed::class,
)]
final class ManageAccount implements EventSourcedUseCase
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private string $accountId;

    // Business logic and event subscribers...
}
```

All parameters are optional — configure only what you need.

### Trigger conditions

#### afterEvents

Create a snapshot every N events since the last snapshot. This is the most common trigger.

```php
#[Snapshot(afterEvents: 500)]
```

When the total event count minus the event count at the last snapshot reaches the threshold, a snapshot is taken after the next save. This includes both the events replayed during reconstitution and any new events applied in the current save. Snapshots are created at approximately every 500 events (e.g., at 503, 1005, 1508...) depending on how many events are applied per save.

#### afterSourcingTime

Create a snapshot when reconstitution (loading + replaying events) takes longer than a threshold.

```php
use Gember\EventSourcing\Util\Time\Duration;

#[Snapshot(afterSourcingTime: new Duration(milliseconds: 500))]
```

The `Duration` class supports both `milliseconds` and `seconds`:

```php
new Duration(milliseconds: 500)
new Duration(seconds: 2)

// Static constructors available for use outside attributes:
Duration::milliseconds(500)
Duration::seconds(2)
```

> **Note:** PHP attributes require `new` expressions, not static method calls. Use `new Duration(milliseconds: 500)` in attributes.

> **Best practice:** Use `afterSourcingTime` as a safety net, not a primary trigger. Since it measures wall-clock time, it's affected by server load and database latency. Prefer `afterEvents` as the primary trigger — it's deterministic and predictable. Combine both for a belt-and-suspenders approach: `#[Snapshot(afterEvents: 500, afterSourcingTime: new Duration(seconds: 2))]`.

#### onEvent / onEvents

Create a snapshot when a specific event type is applied. Useful for milestone events that indicate a natural snapshot point.

```php
// Single event:
#[Snapshot(onEvent: AccountClosed::class)]

// Multiple events:
#[Snapshot(onEvents: [AccountClosed::class, MonthEnded::class])]

// Can combine both:
#[Snapshot(
    onEvent: AccountClosed::class,
    onEvents: [MonthEnded::class, YearEnded::class],
)]
```

### Combining triggers

All triggers can be combined. Any of them fires a snapshot (OR logic, first-match-wins):

```php
#[Snapshot(
    afterEvents: 500,
    afterSourcingTime: new Duration(milliseconds: 500),
    onEvents: [AccountClosed::class, MonthEnded::class],
)]
```

When multiple policies match on the same save, only the first matching policy triggers a snapshot. Exactly one snapshot is created per save at most.

### Serialization

Snapshots serialize the use case's state using the same `Serializer` used for domain events and sagas. Via the `StackedSerializer`:

1. **`SerializableInterfaceSerializer`** — tried first. If the use case implements `Serializable`, it uses `toPayload()`/`fromPayload()` for full control over the snapshot format.
2. **Symfony/default serializer** — fallback. Handles use cases without `Serializable` automatically.

Note that the default Symfony Serializer only serializes **public properties**. Most use cases have private state properties, which means the default serializer will produce incomplete snapshots. There are two solutions:

#### Option A: Configure Symfony to serialize private properties

Register the `PropertyNormalizer` in your Symfony serializer configuration. This normalizer uses reflection to access private properties:

```yaml
# config/packages/framework.yaml
framework:
    serializer:
        default_context:
            # No additional config needed — PropertyNormalizer is auto-registered
            # when symfony/property-info is installed
```

Or register it explicitly as a service with higher priority:

```yaml
# config/services.yaml
services:
    Symfony\Component\Serializer\Normalizer\PropertyNormalizer:
        tags:
            - { name: serializer.normalizer, priority: -500 }
```

This handles all use cases transparently without any code changes.

#### Option B: Implement the Serializable interface

For explicit control over which properties are persisted and how:

```php
use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @implements Serializable<array{accountId: string, balance: int, closed: bool}, ManageAccount>
 */
#[Snapshot(afterEvents: 500)]
final class ManageAccount implements EventSourcedUseCase, Serializable
{
    use EventSourcedUseCaseBehaviorTrait;

    #[DomainTag]
    private string $accountId;
    private int $balance = 0;
    private bool $closed = false;

    // Business logic and event subscribers...

    public function toPayload(): array
    {
        return [
            'accountId' => $this->accountId,
            'balance' => $this->balance,
            'closed' => $this->closed,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        $useCase = new self();
        $useCase->accountId = $payload['accountId'];
        $useCase->balance = $payload['balance'];
        $useCase->closed = $payload['closed'];

        return $useCase;
    }
}
```

> **Which to choose?** Option A is simpler — no code changes needed. Option B gives explicit control and works without a framework. For sagas, the same choice applies (see [Sagas - Serialization](sagas.md#serialization)).

For more details on serialization approaches, see [Domain events - Serialization](domain-events.md#serialization).

### Snapshot policies

The snapshot trigger conditions are implemented as pluggable policies via the `SnapshotPolicy` interface. Three built-in policies handle the attribute parameters:

| Policy | Checks |
|--------|--------|
| `AfterEventsSnapshotPolicy` | Event count since last snapshot >= `afterEvents` |
| `AfterSourcingTimeSnapshotPolicy` | Reconstitution time >= `afterSourcingTime` |
| `OnEventsSnapshotPolicy` | Applied event class matches `onEvent`/`onEvents` |

You can create custom policies by implementing the `SnapshotPolicy` interface:

```php
use Gember\EventSourcing\Snapshot\Policy\SnapshotContext;
use Gember\EventSourcing\Snapshot\Policy\SnapshotPolicy;
use Gember\EventSourcing\Resolver\UseCase\SnapshotDefinition;

final readonly class AlwaysSnapshotPolicy implements SnapshotPolicy
{
    public function shouldSnapshot(SnapshotDefinition $definition, SnapshotContext $context): bool
    {
        return true; // Snapshot on every save
    }
}
```

When using the Symfony bundle, custom policies are automatically picked up when tagged with `gember.snapshot.policy`.

### Snapshot resilience

Snapshots are an optimization, never a requirement for correctness. Both loading and saving snapshots are non-critical — if either fails (e.g., database unavailable, corrupted data), the failure is logged and the system continues normally. A failed load falls back to full event replay. A failed save is skipped. The next successful operation will recover automatically.

#### Automatic stale snapshot recovery

Snapshots are versioned — each save inserts a new row rather than overwriting the previous one. When loading, the system always retrieves the latest snapshot (highest `event_count`).

If a snapshot becomes stale (e.g., the use case class changed and deserialization fails, or the snapshot's `lastEventId` is no longer in the event store), the system automatically falls back to a full event replay. This is logged at `info` level:

```
[Snapshot] Stale snapshot detected, falling back to full replay  {lastEventId: "...", domainTags: [...]}
[Snapshot] Stale snapshot, falling back to full replay           {exception: "...", exceptionClass: "...", domainTags: [...]}
```

The first message indicates a stale `lastEventId` (e.g., event store was rebuilt). The second indicates a deserialization failure (e.g., class structure changed). In both cases, the next successful save will create a fresh snapshot.

This means **truncating `snapshot_store` after deployments is no longer required** — the system auto-recovers. However, truncating is still safe and can be used to reclaim disk space from accumulated old snapshots.

### Symfony bundle configuration

Enable snapshotting in `gember_event_sourcing.yaml`:

```yaml
gember_event_sourcing:
    snapshot:
        enabled: true
```

This activates the snapshot decorator, snapshot store, and all built-in policies. When disabled (default), all snapshot-related services are removed.

### Performance considerations

Snapshot creation happens synchronously during `save()`. For most use cases this is negligible, but for use cases with large state, the serialization + database write adds latency to the saves that trigger a snapshot. This only occurs when a policy threshold is reached — not on every save. Choose trigger thresholds that balance reconstitution speed against snapshot creation frequency.

### Deployment considerations

Snapshots serialize the use case's state. If the use case class changes (e.g., properties renamed, event handlers modified, serialization format changed), existing snapshots may become incompatible. The system handles this automatically — a failed deserialization triggers a full event replay, and a fresh snapshot is created on the next save.

Because snapshots are versioned (insert-only), old snapshots accumulate over time. This is generally harmless, but for large-scale systems you may want to periodically clean up old snapshots:

```sql
-- Remove all snapshots (safe — only affects performance, not correctness)
TRUNCATE TABLE snapshot_store;

-- Or selectively keep the 3 most recent snapshots per boundary (MySQL 8+)
DELETE FROM snapshot_store WHERE id IN (
    SELECT id FROM (
        SELECT id, ROW_NUMBER() OVER (PARTITION BY boundary_hash ORDER BY event_count DESC) AS rn
        FROM snapshot_store
    ) ranked WHERE rn > 3
);
```

### Upgrading from pre-versioned snapshots

If upgrading from a version that used upsert semantics (single snapshot per boundary), the `snapshot_store` table schema has changed:

- New `id` column (primary key, UUID)
- `boundary_hash` is no longer the primary key
- New unique constraint on `(boundary_hash, event_count)`
- `updated_at` column removed (rows are immutable)

Since all packages are 0.x, **drop and recreate the `snapshot_store` table** using the updated schema. Existing snapshots will be rebuilt automatically by the configured policies.

Additionally, `RdbmsSnapshotStore` now requires an `IdentityGenerator` (`Gember\DependencyContracts\Util\Generator\Identity\IdentityGenerator`) as its second constructor argument and a `Clock` (`Gember\EventSourcing\Util\Time\Clock\Clock`) as its third. The `gember/identity-generator-symfony` package provides two implementations: `SymfonyUuidIdentityGenerator` and `SymfonyUlidIdentityGenerator`. If you wire services manually (outside the Symfony bundle or universal service provider):

```php
use Gember\EventSourcing\Snapshot\Rdbms\RdbmsSnapshotStore;
use Gember\EventSourcing\Util\Time\Clock\Native\NativeClock;
use Gember\IdentityGeneratorSymfony\Uuid\SymfonyUuidIdentityGenerator;

$snapshotStore = new RdbmsSnapshotStore(
    $rdbmsSnapshotStoreRepository,
    new SymfonyUuidIdentityGenerator($uuidFactory),
    new NativeClock(),
);
```

### Observability

When snapshotting is enabled, the `LoggableSnapshotStoreDecorator` provides structured logging:

```
[Snapshot] Started loading snapshot    {domainTags: [...]}
[Snapshot] Loaded snapshot             {lastEventId: "...", eventCount: 500, domainTags: [...], duration: 0.002}
[Snapshot] No snapshot found           {domainTags: [...], duration: 0.001}
[Snapshot] Snapshot triggered          {policy: "AfterEventsSnapshotPolicy", domainTags: [...], eventCount: 1000}
[Snapshot] Started saving snapshot     {lastEventId: "...", eventCount: 1000, domainTags: [...]}
[Snapshot] Finished saving snapshot    {domainTags: [...], duration: 0.015}
```

When a snapshot is stale and the system falls back to full event replay, the repository decorator logs at `info` level:

```
[Snapshot] Stale snapshot detected, falling back to full replay  {lastEventId: "...", domainTags: [...]}
[Snapshot] Stale snapshot, falling back to full replay           {exception: "...", exceptionClass: "...", domainTags: [...]}
```

The first message indicates the snapshot's `lastEventId` was not found in the event store. The second indicates deserialization failed (e.g., use case class structure changed). Both result in a full event replay — no data loss.

If a snapshot operation fails, the snapshot store decorator logs at `error` level:

```
[Snapshot] Failed saving snapshot   {exception: "...", exceptionClass: "...", domainTags: [...], duration: 0.003}
[Snapshot] Failed loading snapshot  {exception: "...", exceptionClass: "...", domainTags: [...], duration: 0.001}
```

Additionally, the repository decorator logs at `warning` level when snapshot operations fail but are handled gracefully (falling back to full replay or skipping the snapshot):

```
[Snapshot] Failed loading snapshot, falling back to full replay  {exception: "...", exceptionClass: "...", domainTags: [...]}
[Snapshot] Failed saving snapshot, skipping                      {exception: "...", exceptionClass: "...", domainTags: [...]}
```

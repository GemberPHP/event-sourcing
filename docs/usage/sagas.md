## Sagas

Sagas are **persisted, long-running business processes** that coordinate complex workflows across multiple domain events and use cases. Unlike use cases (which are reconstructed from events), sagas maintain their state by being persisted directly to a saga store.

### Overview

Sagas are particularly useful for:
- **Long-running processes** - Managing workflows that span hours, days, or even weeks
- **Compensating transactions** - Handling rollback scenarios in distributed systems when operations fail
- **Process coordination** - Orchestrating sequential or parallel steps across multiple use cases/aggregates
- **Cross-boundary workflows** - Coordinating processes that span multiple bounded contexts

### Key concepts

**Persisted state**: Unlike event-sourced use cases, sagas are persisted directly. The saga store saves the entire saga instance after each event is processed, making sagas stateful across domain events.

**Domain event subscribing**: Sagas react to domain events using the `#[SagaEventSubscriber]` attribute. When a relevant event occurs, the saga is loaded from the store, processes the event, and is saved again.

**Command recording**: Sagas orchestrate workflows by recording commands through the `CommandRecorder`. Recorded commands are dispatched after the saga state is persisted, preventing re-entrancy issues where synchronous command handling could overwrite saga state. This allows sagas to trigger actions in aggregates or use cases without directly coupling to them.

**Saga linking**: The connection between a domain event and a saga is established through **Saga IDs**. This is the key mechanism that determines which saga instance should handle which event. A saga can have multiple Saga IDs, allowing it to subscribe to events with different identifiers.

**Saga IDs vs Domain Tags**: These are independent concepts that serve different purposes:

| Attribute | Purpose | Used by | Storage |
|-----------|---------|---------|---------|
| `#[DomainTag]` | Event store indexing for use case loading | Use cases | Event store relations |
| `#[SagaId]` | Saga routing and identification | Sagas | Saga store relations |

A property on a domain event can have both `#[DomainTag]` and `#[SagaId]`, one, or neither. They do not affect each other.

### Defining a saga

A saga is a simple PHP class that can be configured in two ways:

#### Option 1: Using the #[Saga] attribute

Define the saga name explicitly using the `#[Saga]` attribute:

```php
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Saga\Attribute\SagaId;

#[Saga(name: 'order.fulfillment')]
final class OrderFulfillmentSaga
{
    #[SagaId]
    public string $orderId;

    private bool $paymentReceived = false;
    private bool $itemsShipped = false;

    // Event subscribers will go here
}
```

#### Option 2: Implementing the NamedSaga interface

For dynamic saga naming or when you need more control, implement the `NamedSaga` interface:

```php
use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\Saga\NamedSaga;

final class OrderFulfillmentSaga implements NamedSaga
{
    #[SagaId(name: 'orderId')]
    public string $orderId;

    private bool $paymentReceived = false;
    private bool $itemsShipped = false;

    public static function getName(): string
    {
        return 'order.fulfillment';
    }
}
```

### Saga IDs

Saga IDs link events to specific saga instances. Each saga must have at least one Saga ID property marked with the `#[SagaId]` attribute.

**Requirements for Saga ID properties:**
- **Must be public** - Saga ID properties must have public visibility
- **May be uninitialized** - A new saga instance is created before the first event subscriber populates the Saga ID properties, so they can be uninitialized at that point. Use `isset()` to check, or make the property nullable (`?string`) and initialize to `null` as an alternative
- **Must be serializable** - Values must be serializable, either primitive, `Stringable` or a serializable Value Object
- **Must be set in the first subscriber** - The event subscriber with `CreationPolicy::IfMissing` must assign the Saga ID properties known from the triggering event; Saga IDs that only become known later can be assigned by later subscribers

**Custom naming:**
- Use `#[SagaId(name: 'customName')]` to specify a custom Saga ID name
- Omit the `name` parameter to use the property name as the Saga ID name

**Multiple Saga IDs:**
A saga can have multiple Saga ID properties, allowing it to be triggered by events with different identifiers:

```php
#[Saga(name: 'order.fulfillment')]
final class OrderFulfillmentSaga
{
    #[SagaId(name: 'orderId')]
    public string $orderId;

    #[SagaId] // Uses property name 'shipmentId' as Saga ID name
    public string $shipmentId;

    // Saga can be triggered by events with either orderId or shipmentId
}
```

Events route to sagas using **OR logic**: if any of the event's `#[SagaId]` properties match a saga's registered Saga ID name, the saga receives the event. This means different events can find the same saga instance through different identifiers. For example:
- `OrderPlacedEvent` (with `#[SagaId] $orderId`) routes via `orderId`
- `ShipmentDeliveredEvent` (with `#[SagaId] $shipmentId`) routes via `shipmentId`

Both reach the same `OrderFulfillmentSaga` type, but locate the saga instance through different Saga ID values. Each Saga ID value is stored as a separate relation in the saga store, enabling lookup by any of them.

!!! warning "A Saga ID value identifies one saga instance"
    A Saga ID value belongs to at most one saga instance per saga name, across all its Saga ID names. Only use identifiers that relate one-to-one to a saga instance: an order saga can have an `orderId` and a `shipmentId`, but not a `customerId`, as a customer places many orders. The event placing a customer's second order would find the first order's saga through the `customerId`, and be handled by it. Adding a Saga ID value that already belongs to another instance to an existing saga throws a `SagaIdAlreadyInUseException`. To react to customer events for all of a customer's orders, use a separate saga per customer, or let the customer's events carry the order IDs.

### Saga event subscribers

Sagas subscribe to domain events using the `#[SagaEventSubscriber]` attribute. Event subscriber methods react to domain events and coordinate the saga's workflow.

#### Method signature requirements

Event subscriber methods must follow this signature:

```php
#[SagaEventSubscriber]
public function methodName(EventClass $event, CommandRecorder $commandRecorder): void
```

**Parameters:**
- **First parameter** - The domain event (type-hinted with the event class)
- **Second parameter** - Instance of `CommandRecorder` for recording commands to be dispatched after saga persistence (required)
- **Return type** - Must be `void`

#### CreationPolicy

The `#[SagaEventSubscriber]` attribute accepts a `policy` parameter that controls what happens when the saga instance doesn't exist yet:

- **`CreationPolicy::Never`** (default) - Skip processing if saga not found
  - Use for events that progress an existing saga
  - Processing is silently skipped if the saga doesn't exist

- **`CreationPolicy::IfMissing`** - Create a new saga instance if not found
  - Use for the event that starts the saga
  - A new saga instance is created and then the event is processed

```php
use Gember\EventSourcing\Saga\CommandRecorder;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Saga\Attribute\SagaEventSubscriber;
use Gember\EventSourcing\Saga\Attribute\SagaId;

#[Saga(name: 'order.fulfillment')]
final class OrderFulfillmentSaga
{
    #[SagaId]
    public ?string $orderId = null;

    private bool $paymentReceived = false;
    private bool $itemsShipped = false;

    /**
     * Starts the fulfillment saga when an order is placed.
     * Uses IfMissing to create a new saga instance.
     */
    #[SagaEventSubscriber(policy: CreationPolicy::IfMissing)]
    public function onOrderPlacedEvent(OrderPlacedEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->orderId = $event->orderId;

        // Dispatch commands to start the fulfillment process
        $commandRecorder->record(new ProcessPaymentCommand($event->orderId, $event->amount));
    }

    /**
     * Progresses the saga when payment is received.
     * Uses Never (default) - saga must already exist.
     */
    #[SagaEventSubscriber]
    public function onPaymentReceivedEvent(PaymentReceivedEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->paymentReceived = true;

        // Dispatch command to ship the order
        $commandRecorder->record(new ShipOrderCommand($event->orderId));
    }

    /**
     * Completes the saga when order is shipped.
     */
    #[SagaEventSubscriber]
    public function onOrderShippedEvent(OrderShippedEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->itemsShipped = true;

        // Dispatch command to notify the customer
        $commandRecorder->record(new SendShippingNotificationCommand($event->orderId));
    }
}
```

### Linking domain events to sagas

The connection between domain events and saga instances is established through **Saga IDs**. This routing mechanism ensures the correct saga instance processes the correct events.

#### How Saga ID routing works

**Step 1: Mark Saga ID properties on the saga**

Define Saga ID properties on your saga using `#[SagaId]`:

```php
#[Saga(name: 'order.fulfillment')]
final class OrderFulfillmentSaga
{
    #[SagaId(name: 'orderId')]
    public ?string $orderId = null;

    #[SagaId] // Uses property name 'shipmentId' as Saga ID name
    public ?string $shipmentId = null;
}
```

**Step 2: Mark Saga ID properties on domain events**

Mark the corresponding properties in your domain events with the **same Saga ID names**:

```php
use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;

#[DomainEvent(name: 'shipment.scheduled')]
final readonly class ShipmentScheduledEvent
{
    public function __construct(
        #[DomainTag]
        #[SagaId] // Uses property name 'shipmentId'
        public string $shipmentId,
        #[DomainTag]
        #[SagaId(name: 'orderId')]
        public string $forOrder,
    ) {}
}

#[DomainEvent(name: 'shipment.delivered')]
final readonly class ShipmentDeliveredEvent
{
    public function __construct(
        #[DomainTag]
        #[SagaId] // Uses property name 'shipmentId'
        public string $shipmentId,
        // No orderId - this event only routes via shipmentId
    ) {}
}
```

#### The routing flow

When a domain event is published, the following process occurs:

1. **Extract Saga IDs** - The `SagaEventHandler` extracts all Saga ID values from the event
2. **Route by Saga ID** - For each Saga ID in the event (e.g., `orderId`, `shipmentId`):
   - If the Saga ID value is **null**, that routing path is skipped
   - Otherwise, it finds all saga classes registered for that Saga ID name
3. **Match event subscribers** - For each matching saga class, checks if there's an event subscriber for this specific event type. The event's Saga ID values are grouped per saga class and subscriber method
4. **Retrieve saga instance** - Loads the saga instance owning any of the grouped Saga ID values from the saga store
5. **Handle missing sagas**:
   - With `CreationPolicy::IfMissing` - A new saga instance is created
   - With `CreationPolicy::Never` - Processing is skipped
6. **Invoke subscriber** - The saga's event subscriber method is invoked with the event and CommandRecorder
7. **Persist saga** - The saga instance is persisted back to the saga store, after which the recorded commands are dispatched
8. **Remaining Saga IDs** - Grouped Saga ID values not belonging to the handled saga instance can only belong to other instances: these existing instances handle the event too (they are never created for it)

**What this enables:**
- Multiple events with the same Saga ID route to the same saga instance (saga continuity)
- A single saga can be triggered by events with different Saga IDs (cross-context coordination)
- A single event can trigger multiple different sagas (parallel saga execution)
- Flexible saga coordination across multiple domain concepts

> **Note:** When a Saga ID value on an event is null, that routing path is skipped and won't trigger the saga. Only non-null Saga ID values are used for saga storage and retrieval.

### Saga persistence

Unlike event-sourced use cases, sagas are persisted directly. The saga store saves the entire saga instance after each event is processed. This involves two storage structures:

| Table | Purpose |
|-------|---------|
| Saga store | Stores serialized saga instances (ID, name, payload, timestamps, revision) |
| Saga store relations | Links Saga ID values to saga instances for routing |

When a saga instance is saved, all current Saga ID values are persisted as relations. On subsequent saves, relations of removed Saga ID values are deleted and relations of added ones are inserted, allowing Saga ID values to change over the saga's lifetime. A Saga ID value belongs to at most one saga instance per saga name (see [Saga IDs](#saga-ids)).

Concurrent events for the same saga instance are handled safely: each save checks the revision the saga was loaded with, and a save that conflicts with a concurrent one is retried with the reloaded saga (up to 5 attempts). See [How it works - Saga concurrency](../how-it-works.md#saga-concurrency).

#### Upgrading the saga store

The saga store got a revision, a saga name per Saga ID with a unique key (case-sensitive), and lost its lock table. The released migrations were changed in place, so **a database that already ran them needs these changes applied manually**. Old and new code can't run against each other's schema, so upgrade with everything stopped:

1. Take a backup.
2. Stop all processes handling saga events (outbox processors, message consumers).
3. Run the pre-flight query below and resolve every row it returns.
4. Apply the upgrade SQL.
5. Deploy the new version, then start the processes again.

**Pre-flight: Saga ID values owned by more than one saga.** These block the unique key:

```sql
-- MySQL / MariaDB
SELECT s.saga_name, MIN(r.saga_id) AS saga_id, GROUP_CONCAT(s.id ORDER BY s.created_at) AS saga_store_ids
FROM saga_store_relation r
JOIN saga_store s ON s.id = r.id
GROUP BY s.saga_name, r.saga_id COLLATE utf8mb4_bin
HAVING COUNT(DISTINCT s.id) > 1;

-- SQLite
SELECT s.saga_name, r.saga_id, GROUP_CONCAT(s.id) AS saga_store_ids
FROM saga_store_relation r
JOIN saga_store s ON s.id = r.id
GROUP BY s.saga_name, r.saga_id
HAVING COUNT(DISTINCT s.id) > 1;
```

- **A Saga ID that isn't one-to-one with a saga** (such as a `customerId` on order sagas, see [Saga IDs](#saga-ids)): remove it from the saga class, and delete only its relation rows (`DELETE FROM saga_store_relation WHERE saga_id = ? AND id IN (…)`). Don't delete these sagas: they are separate, live sagas.
- **The same saga created twice** (by concurrent events in earlier versions): keep one, typically the oldest, and delete the others: first their rows in `saga_store_relation`, then in `saga_store`.

**Upgrade SQL (MySQL / MariaDB):**

```sql
ALTER TABLE saga_store ADD COLUMN revision int NOT NULL DEFAULT 0;

ALTER TABLE saga_store_relation
  ADD COLUMN saga_name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' AFTER id,
  MODIFY saga_id varchar(50) COLLATE utf8mb4_bin NOT NULL;

UPDATE saga_store_relation r JOIN saga_store s ON s.id = r.id SET r.saga_name = s.saga_name WHERE r.saga_name = '';

ALTER TABLE saga_store_relation
  ADD UNIQUE KEY saga_name_saga_id (saga_name, saga_id),
  ALTER COLUMN saga_name DROP DEFAULT;

DROP TABLE IF EXISTS saga_store_lock;

-- Only for databases created with version 0.13.0 or later (fails with error 1091 otherwise, which is fine)
ALTER TABLE saga_store_relation DROP KEY saga_id;
```

The result is identical to a fresh install. Schema changes aren't transactional: if the unique key fails (a duplicate the pre-flight didn't resolve), the statements before it are applied already. Resolve the duplicate and continue from the `UPDATE`.

**Upgrade SQL (SQLite):**

```sql
ALTER TABLE saga_store ADD COLUMN revision int NOT NULL DEFAULT 0;
ALTER TABLE saga_store_relation ADD COLUMN saga_name varchar(255) NOT NULL DEFAULT '';
UPDATE saga_store_relation SET saga_name = (SELECT s.saga_name FROM saga_store s WHERE s.id = saga_store_relation.id) WHERE saga_name = '';
CREATE UNIQUE INDEX saga_name_saga_id ON saga_store_relation (saga_name, saga_id);
DROP TABLE IF EXISTS saga_store_lock;
```

This is equivalent to a fresh install (the column order and default differ).

**Deleted migration.** The migration that created `saga_store_lock` (Doctrine `Version20260818130000`, Phinx `20260818130000`, released in 0.13.0) no longer exists. Remove it from the migration history:

```bash
bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260818130000' --delete --no-interaction
```

For Phinx: `DELETE FROM phinxlog WHERE version = 20260818130000;`. Migrations copied into your application need the same changes.

**Rolling back** to the previous version after the upgrade (MySQL / MariaDB; the `revision` column can stay):

```sql
CREATE TABLE IF NOT EXISTS saga_store_lock (
  boundary_hash char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (boundary_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE saga_store_relation
  DROP KEY saga_name_saga_id,
  DROP COLUMN saga_name,
  MODIFY saga_id varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  ADD KEY saga_id (saga_id);
```

### Serialization

Sagas are serialized when persisted to the saga store and deserialized when loaded. _Gember Event Sourcing_ uses the same configurable `Serializer` as domain events, supporting two approaches.

#### Option 1: Automatic serialization (Recommended)

When using framework integrations like [gember/event-sourcing-symfony-bundle](https://github.com/GemberPHP/event-sourcing-symfony-bundle), sagas are automatically serialized using the Symfony Serializer component. This means **you don't need to implement any interface** - just define your saga as a regular class:

```php
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Saga\Attribute\SagaId;

#[Saga(name: 'order.fulfillment')]
final class OrderFulfillmentSaga
{
    #[SagaId]
    public ?string $orderId = null;

    private bool $paymentReceived = false;
    private bool $itemsShipped = false;

    // Event subscribers...
}
```

Public properties are automatically serialized and deserialized. Note that **private properties are not serialized** by the default Symfony Serializer — if your saga uses private properties for state, use the `Serializable` interface (Option 2) instead.

This is the **recommended approach** for sagas that use only public properties.

#### Option 2: Explicit serialization with Serializable interface

For more control over which properties are persisted and how, implement the `Serializable` interface:

```php
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @implements Serializable<array{
 *     orderId: ?string,
 *     paymentReceived: bool,
 *     itemsShipped: bool
 * }, OrderFulfillmentSaga>
 */
#[Saga(name: 'order.fulfillment')]
final class OrderFulfillmentSaga implements Serializable
{
    #[SagaId]
    public ?string $orderId = null;

    private bool $paymentReceived = false;
    private bool $itemsShipped = false;

    // Event subscribers...

    public function toPayload(): array
    {
        return [
            'orderId' => $this->orderId,
            'paymentReceived' => $this->paymentReceived,
            'itemsShipped' => $this->itemsShipped,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        $saga = new self();
        $saga->orderId = $payload['orderId'];
        $saga->paymentReceived = $payload['paymentReceived'];
        $saga->itemsShipped = $payload['itemsShipped'];

        return $saga;
    }
}
```

**When to use explicit serialization:**
- Your saga has **private properties** that need to be persisted (the default Symfony Serializer only handles public properties)
- You need control over the serialization format (e.g., excluding transient properties)
- You want to handle schema evolution when saga structure changes
- You prefer explicit, testable serialization logic

#### How the StackedSerializer works

When using the Symfony bundle, both serialization approaches work together via the `StackedSerializer`:

1. **First**, it tries `SerializableInterfaceSerializer` - for sagas implementing `Serializable`
2. **Then**, it falls back to `SymfonySerializer` - for automatic serialization
3. **Finally**, if both fail, it throws an exception with detailed error information

This is the same mechanism used for [domain event serialization](domain-events.md#serialization), and you can mix both approaches across different sagas in the same application.

### Examples

#### Example 1: Simple order processing saga

A basic saga that coordinates a simple order fulfillment process:

```php
use Gember\EventSourcing\Saga\CommandRecorder;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Saga\Attribute\SagaEventSubscriber;
use Gember\EventSourcing\Saga\Attribute\SagaId;

#[Saga(name: 'order.processing')]
final class OrderProcessingSaga
{
    #[SagaId]
    public ?string $orderId = null;

    private bool $paymentProcessed = false;
    private bool $orderShipped = false;

    #[SagaEventSubscriber(policy: CreationPolicy::IfMissing)]
    public function onOrderPlacedEvent(OrderPlacedEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->orderId = $event->orderId;

        // Start payment processing
        $commandRecorder->record(new ProcessPaymentCommand($event->orderId, $event->amount));
    }

    #[SagaEventSubscriber]
    public function onPaymentProcessedEvent(PaymentProcessedEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->paymentProcessed = true;

        // Proceed to shipping
        $commandRecorder->record(new ShipOrderCommand($event->orderId));
    }

    #[SagaEventSubscriber]
    public function onOrderShippedEvent(OrderShippedEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->orderShipped = true;

        // Notify customer
        $commandRecorder->record(new NotifyCustomerCommand($event->orderId));
    }
}
```

**Key characteristics:**
- **Single Saga ID** - Uses only `orderId` for routing
- **Linear workflow** - Events progress the saga through sequential steps
- **Simple coordination** - Dispatches commands to trigger next steps

#### Example 2: Complex fulfillment saga with compensating transactions

A more complex saga demonstrating multiple Saga IDs, parallel processing, and compensating transactions. The shipment ID is only known once the shipment is scheduled; from then on, shipment events find the saga through it:

```php
use Gember\EventSourcing\Saga\CommandRecorder;
use Gember\EventSourcing\Common\CreationPolicy;
use Gember\EventSourcing\Saga\Attribute\Saga;
use Gember\EventSourcing\Saga\Attribute\SagaEventSubscriber;
use Gember\EventSourcing\Saga\Attribute\SagaId;

#[Saga(name: 'order.fulfillment')]
final class OrderFulfillmentSaga
{
    #[SagaId]
    public ?string $orderId = null;

    #[SagaId]
    public ?string $shipmentId = null;

    private bool $paymentReceived = false;
    private bool $inventoryReserved = false;
    private bool $itemsShipped = false;

    #[SagaEventSubscriber(policy: CreationPolicy::IfMissing)]
    public function onOrderPlacedEvent(OrderPlacedEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->orderId = $event->orderId;

        // Start the fulfillment process
        $commandRecorder->record(new ProcessPaymentCommand($event->orderId, $event->amount));
        $commandRecorder->record(new ReserveInventoryCommand($event->orderId, $event->items));
    }

    #[SagaEventSubscriber]
    public function onPaymentReceivedEvent(PaymentReceivedEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->paymentReceived = true;

        // Check if we can proceed to shipping
        if ($this->inventoryReserved) {
            $commandRecorder->record(new ShipOrderCommand($event->orderId));
        }
    }

    #[SagaEventSubscriber]
    public function onInventoryReservedEvent(InventoryReservedEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->inventoryReserved = true;

        // Check if we can proceed to shipping
        if ($this->paymentReceived) {
            $commandRecorder->record(new ShipOrderCommand($event->orderId));
        }
    }

    #[SagaEventSubscriber]
    public function onPaymentFailedEvent(PaymentFailedEvent $event, CommandRecorder $commandRecorder): void
    {
        // Compensating transaction: release inventory
        if ($this->inventoryReserved) {
            $commandRecorder->record(new ReleaseInventoryCommand($event->orderId));
        }

        $commandRecorder->record(new CancelOrderCommand($event->orderId));
    }

    #[SagaEventSubscriber]
    public function onInventoryUnavailableEvent(InventoryUnavailableEvent $event, CommandRecorder $commandRecorder): void
    {
        // Compensating transaction: refund payment
        if ($this->paymentReceived) {
            $commandRecorder->record(new RefundPaymentCommand($event->orderId));
        }

        $commandRecorder->record(new CancelOrderCommand($event->orderId));
    }

    #[SagaEventSubscriber]
    public function onShipmentScheduledEvent(ShipmentScheduledEvent $event, CommandRecorder $commandRecorder): void
    {
        // Routed via orderId; from now on the saga can also be found via its shipmentId
        $this->shipmentId = $event->shipmentId;
    }

    #[SagaEventSubscriber]
    public function onShipmentDelayedEvent(ShipmentDelayedEvent $event, CommandRecorder $commandRecorder): void
    {
        // Routed via shipmentId: shipment events don't know the order
        if (!$this->itemsShipped) {
            $commandRecorder->record(new NotifyCustomerOfDelayCommand($this->orderId, $event->expectedAt));
        }
    }

    #[SagaEventSubscriber]
    public function onShipmentDeliveredEvent(ShipmentDeliveredEvent $event, CommandRecorder $commandRecorder): void
    {
        $this->itemsShipped = true;

        // Notify customer and complete the saga
        $commandRecorder->record(new SendShippingNotificationCommand($this->orderId));
    }
}
```

**Key characteristics:**
- **Multiple Saga IDs** - Can be triggered by events with `orderId` or, once known, `shipmentId`
- **Parallel processing** - Payment and inventory reservation happen concurrently
- **Compensating transactions** - Rolls back partial operations when failures occur
- **Cross-context coordination** - Responds to both order and shipment events
- **State-based decisions** - Uses boolean flags to coordinate parallel steps

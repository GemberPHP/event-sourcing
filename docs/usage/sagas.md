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

**Command dispatching**: Sagas orchestrate workflows by dispatching commands to other parts of the system through the `CommandBus`. This allows sagas to trigger actions in aggregates or use cases without directly coupling to them.

**Saga linking**: The connection between a domain event and a saga is established through **Saga IDs**. This is the key mechanism that determines which saga instance should handle which event. A saga can have multiple Saga IDs, allowing it to subscribe to events with different identifiers.

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
- **Must be serializable** - Values must be serializable, either primitive, `Stringable` or a serializable Value Object.

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

    #[SagaId] // Uses property name 'customerId' as Saga ID name
    public string $customerId;

    // Saga can be triggered by events with either orderId or customerId
}
```

### Saga event subscribers

Sagas subscribe to domain events using the `#[SagaEventSubscriber]` attribute. Event subscriber methods react to domain events and coordinate the saga's workflow.

#### Method signature requirements

Event subscriber methods must follow this signature:

```php
#[SagaEventSubscriber]
public function methodName(EventClass $event, CommandBus $commandBus): void
```

**Parameters:**
- **First parameter** - The domain event (type-hinted with the event class)
- **Second parameter** - Instance of `CommandBus` for dispatching commands (required)
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
use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;
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
    public function onOrderPlacedEvent(OrderPlacedEvent $event, CommandBus $commandBus): void
    {
        $this->orderId = $event->orderId;

        // Dispatch commands to start the fulfillment process
        $commandBus->handle(new ProcessPaymentCommand($event->orderId, $event->amount));
    }

    /**
     * Progresses the saga when payment is received.
     * Uses Never (default) - saga must already exist.
     */
    #[SagaEventSubscriber]
    public function onPaymentReceivedEvent(PaymentReceivedEvent $event, CommandBus $commandBus): void
    {
        $this->paymentReceived = true;

        // Dispatch command to ship the order
        $commandBus->handle(new ShipOrderCommand($event->orderId));
    }

    /**
     * Completes the saga when order is shipped.
     */
    #[SagaEventSubscriber]
    public function onOrderShippedEvent(OrderShippedEvent $event, CommandBus $commandBus): void
    {
        $this->itemsShipped = true;

        // Dispatch command to notify the customer
        $commandBus->handle(new SendShippingNotificationCommand($event->orderId));
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

    #[SagaId] // Uses property name 'customerId' as Saga ID name
    public ?string $customerId = null;
}
```

**Step 2: Mark Saga ID properties on domain events**

Mark the corresponding properties in your domain events with the **same Saga ID names**:

```php
use Gember\EventSourcing\Saga\Attribute\SagaId;
use Gember\EventSourcing\UseCase\Attribute\DomainEvent;
use Gember\EventSourcing\UseCase\Attribute\DomainTag;

#[DomainEvent(name: 'order.placed')]
final readonly class OrderPlacedEvent
{
    public function __construct(
        #[DomainTag]
        #[SagaId(name: 'orderId')]
        public string $id,
        #[DomainTag]
        #[SagaId] // Links to 'customerId' Saga ID
        public string $customerId,
        public float $amount,
    ) {}
}

#[DomainEvent(name: 'payment.received')]
final readonly class PaymentReceivedEvent
{
    public function __construct(
        #[DomainTag]
        #[SagaId] // Uses property name 'orderId'
        public string $orderId,
        // No customerId - this event only routes via orderId
    ) {}
}
```

#### The routing flow

When a domain event is published, the following process occurs:

1. **Extract Saga IDs** - The `SagaEventHandler` extracts all Saga ID values from the event
2. **Route by Saga ID** - For each Saga ID in the event (e.g., `orderId`, `customerId`):
   - If the Saga ID value is **null**, that routing path is skipped
   - Otherwise, it finds all saga classes registered for that Saga ID name
3. **Match event subscribers** - For each matching saga class, checks if there's an event subscriber for this specific event type
4. **Retrieve saga instance** - Loads the saga instance from the saga store using the Saga ID value
5. **Handle missing sagas**:
   - With `CreationPolicy::IfMissing` - A new saga instance is created
   - With `CreationPolicy::Never` - Processing is skipped
6. **Invoke subscriber** - The saga's event subscriber method is invoked with the event and CommandBus
7. **Persist saga** - The saga instance is persisted back to the saga store

**What this enables:**
- Multiple events with the same Saga ID route to the same saga instance (saga continuity)
- A single saga can be triggered by events with different Saga IDs (cross-context coordination)
- A single event can trigger multiple different sagas (parallel saga execution)
- Flexible saga coordination across multiple domain concepts

> **Note:** When a Saga ID value on an event is null, that routing path is skipped and won't trigger the saga. Only non-null Saga ID values are used for saga storage and retrieval.

### Examples

#### Example 1: Simple order processing saga

A basic saga that coordinates a simple order fulfillment process:

```php
use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;
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
    public function onOrderPlacedEvent(OrderPlacedEvent $event, CommandBus $commandBus): void
    {
        $this->orderId = $event->orderId;

        // Start payment processing
        $commandBus->handle(new ProcessPaymentCommand($event->orderId, $event->amount));
    }

    #[SagaEventSubscriber]
    public function onPaymentProcessedEvent(PaymentProcessedEvent $event, CommandBus $commandBus): void
    {
        $this->paymentProcessed = true;

        // Proceed to shipping
        $commandBus->handle(new ShipOrderCommand($event->orderId));
    }

    #[SagaEventSubscriber]
    public function onOrderShippedEvent(OrderShippedEvent $event, CommandBus $commandBus): void
    {
        $this->orderShipped = true;

        // Notify customer
        $commandBus->handle(new NotifyCustomerCommand($event->orderId));
    }
}
```

**Key characteristics:**
- **Single Saga ID** - Uses only `orderId` for routing
- **Linear workflow** - Events progress the saga through sequential steps
- **Simple coordination** - Dispatches commands to trigger next steps

#### Example 2: Complex fulfillment saga with compensating transactions

A more complex saga demonstrating multiple Saga IDs, parallel processing, and compensating transactions:

```php
use Gember\DependencyContracts\Util\Messaging\MessageBus\CommandBus;
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
    public ?string $customerId = null;

    private bool $paymentReceived = false;
    private bool $inventoryReserved = false;
    private bool $itemsShipped = false;

    #[SagaEventSubscriber(policy: CreationPolicy::IfMissing)]
    public function onOrderPlacedEvent(OrderPlacedEvent $event, CommandBus $commandBus): void
    {
        $this->orderId = $event->orderId;
        $this->customerId = $event->customerId;

        // Start the fulfillment process
        $commandBus->handle(new ProcessPaymentCommand($event->orderId, $event->amount));
        $commandBus->handle(new ReserveInventoryCommand($event->orderId, $event->items));
    }

    #[SagaEventSubscriber]
    public function onPaymentReceivedEvent(PaymentReceivedEvent $event, CommandBus $commandBus): void
    {
        $this->paymentReceived = true;

        // Check if we can proceed to shipping
        if ($this->inventoryReserved) {
            $commandBus->handle(new ShipOrderCommand($event->orderId));
        }
    }

    #[SagaEventSubscriber]
    public function onInventoryReservedEvent(InventoryReservedEvent $event, CommandBus $commandBus): void
    {
        $this->inventoryReserved = true;

        // Check if we can proceed to shipping
        if ($this->paymentReceived) {
            $commandBus->handle(new ShipOrderCommand($event->orderId));
        }
    }

    #[SagaEventSubscriber]
    public function onPaymentFailedEvent(PaymentFailedEvent $event, CommandBus $commandBus): void
    {
        // Compensating transaction: release inventory
        if ($this->inventoryReserved) {
            $commandBus->handle(new ReleaseInventoryCommand($event->orderId));
        }

        $commandBus->handle(new CancelOrderCommand($event->orderId));
    }

    #[SagaEventSubscriber]
    public function onInventoryUnavailableEvent(InventoryUnavailableEvent $event, CommandBus $commandBus): void
    {
        // Compensating transaction: refund payment
        if ($this->paymentReceived) {
            $commandBus->handle(new RefundPaymentCommand($event->orderId));
        }

        $commandBus->handle(new CancelOrderCommand($event->orderId));
    }

    #[SagaEventSubscriber]
    public function onOrderShippedEvent(OrderShippedEvent $event, CommandBus $commandBus): void
    {
        $this->itemsShipped = true;

        // Notify customer and complete the saga
        $commandBus->handle(new SendShippingNotificationCommand($event->orderId));
    }

    #[SagaEventSubscriber]
    public function onCustomerAddressChangedEvent(CustomerAddressChangedEvent $event, CommandBus $commandBus): void
    {
        // This saga can also respond to customer events via the customerId Saga ID
        // If shipping hasn't occurred yet, update the shipping address
        if (!$this->itemsShipped && $this->inventoryReserved) {
            $commandBus->handle(new UpdateShippingAddressCommand($this->orderId, $event->newAddress));
        }
    }
}
```

**Key characteristics:**
- **Multiple Saga IDs** - Can be triggered by events with `orderId` or `customerId`
- **Parallel processing** - Payment and inventory reservation happen concurrently
- **Compensating transactions** - Rolls back partial operations when failures occur
- **Cross-context coordination** - Responds to both order and customer events
- **State-based decisions** - Uses boolean flags to coordinate parallel steps

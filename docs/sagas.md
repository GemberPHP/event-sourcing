## Sagas

Sagas are **persisted, long-running business processes** that coordinate complex workflows across multiple domain events and use cases. Unlike use cases (which are reconstructed from events), sagas maintain their state by being persisted directly to a saga store.

Sagas are particularly useful for:
- Long-running processes: Managing workflows that span hours, days, or even weeks
- Compensating transactions: Handling rollback scenarios in distributed systems
- Process management: Coordinating sequential or parallel steps in a business process

### Key concepts

**Persisted state**: Unlike event-sourced use cases, sagas are persisted directly. The saga store saves the entire saga instance after each event is processed, making sagas stateful across domain events.

**Domain event subscribing**: Sagas react to domain events using the `#[SagaEventSubscriber]` attribute. When a relevant event occurs, the saga is loaded from the store, processes the event, and is saved again.

**Command dispatching**: Sagas orchestrate workflows by dispatching commands to other parts of the system through the `CommandBus`. This allows sagas to trigger actions in aggregates or use cases without directly coupling to them.

**Saga linking**: The connection between a domain event and a saga is established through **Saga IDs**. This is the key mechanism that determines which saga instance should handle which event. A saga can have multiple Saga IDs, allowing it to subscribe to events with different identifiers.

### Configuring a Saga

A saga is a simple PHP class that can be configured in two ways:

1. Use the `#[Saga]` attribute to define the saga name
2. Implement the `NamedSaga` interface for more control

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

    // Event subscribers and logic will go here
}
```

Or using the interface:

```php
use Gember\EventSourcing\Saga\NamedSaga;
use Gember\EventSourcing\Saga\Attribute\SagaId;

final class OrderFulfillmentSaga implements NamedSaga
{
    #[SagaId(name: 'orderId')] // optional to set a custom name, otherwise it will use the property name
    public ?string $orderId = null;

    private bool $paymentReceived = false;
    private bool $itemsShipped = false;

    public static function getName(): string
    {
        return 'order.fulfillment';
    }
}
```

### Saga event subscribers

Sagas subscribe to domain events using the `#[SagaEventSubscriber]` attribute. Each subscriber method receives the event and a `CommandBus` instance for dispatching commands.

The subscriber supports a `CreationPolicy` that determines what happens when the saga instance doesn't exist yet:
- `CreationPolicy::Never` (default): Skip processing if saga not found
- `CreationPolicy::IfMissing`: Create a new saga instance if not found. This is typically used for the saga starting domain event

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

    #[SagaEventSubscriber(policy: CreationPolicy::IfMissing)]
    public function onOrderPlacedEvent(OrderPlacedEvent $event, CommandBus $commandBus): void
    {
        $this->orderId = $event->orderId;

        // Dispatch command to start payment processing
        $commandBus->handle(new ProcessPaymentCommand($event->orderId, $event->amount));
    }

    #[SagaEventSubscriber]
    public function onPaymentReceivedEvent(PaymentReceivedEvent $event, CommandBus $commandBus): void
    {
        $this->paymentReceived = true;

        // Dispatch command to ship items
        $commandBus->handle(new ShipOrderCommand($event->orderId));
    }

    #[SagaEventSubscriber]
    public function onOrderShippedEvent(OrderShippedEvent $event, CommandBus $commandBus): void
    {
        $this->itemsShipped = true;

        // Dispatch command to notify customer
        $commandBus->handle(new SendShippingNotificationCommand($event->orderId));
    }
}
```

### Linking domain events to sagas

The connection between a domain event and a saga instance is established through **Saga IDs**. This is a crucial mechanism that ensures the correct saga instance processes the correct events.

A saga can have **multiple Saga ID properties**, each marked with the `#[SagaId]` attribute. This allows a saga to subscribe to events with different identifiers, making it easier to coordinate complex workflows that involve multiple domain concepts.

**Step 1**: Define one or more Saga ID properties in your saga with specific names using `#[SagaId(name: 'someName')]` or omit the custom ID name; then the property name will be the Saga ID name:

```php
#[Saga(name: 'order.fulfillment')]
final class OrderFulfillmentSaga
{
    #[SagaId(name: 'orderId')]
    public string $orderId;

    #[SagaId] // property name 'customerId' will be used as Saga ID name
    public string $customerId;

    // ... rest of saga
}
```

**Step 2**: Mark the corresponding properties in your domain events with the **same Saga ID names** or omit the custom ID names; then the property names will be the Saga ID names:

```php
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
        #[SagaId]
        public string $orderId,
        // No customerId here - this event only routes via orderId
    ) {}
}
```

**How it works**:

1. When a domain event is published, the `SagaEventHandler` extracts all Saga ID values from the event
2. For each Saga ID in the event (e.g., `'orderId'`, `'customerId'`):
   - If the Saga ID value is **null**, that routing path is skipped
   - Otherwise, it finds all saga classes registered for that Saga ID name
3. For each matching saga class, it checks if there's an event subscriber for this specific event type
4. It retrieves the saga instance from the saga store using the Saga ID value from the event
5. If the saga doesn't exist:
   - With `CreationPolicy::IfMissing`: A new saga instance is created
   - With `CreationPolicy::Never`: Processing is skipped for this saga
6. The saga's event subscriber method is invoked
7. The saga instance is persisted back to the saga store

This mechanism allows:
- Multiple events with the same Saga ID to be routed to the same saga instance
- A single saga to be triggered by events with **different** Saga IDs (e.g., both order events and customer events)
- Flexible saga coordination across multiple domain concepts

> **Note**: Both domain events and sagas can have multiple Saga IDs.
> A domain event with multiple Saga IDs can trigger multiple different sagas,
> and a saga with multiple Saga IDs can be triggered by events with any of those IDs.
> Each Saga ID acts as an independent routing mechanism.

### Examples

A complete example of an order fulfillment saga with multiple Saga IDs:

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

This example demonstrates how a saga with multiple Saga IDs (`orderId` and `customerId`) can:
- Be triggered by order-related events (via `orderId`)
- Be triggered by customer-related events (via `customerId`)

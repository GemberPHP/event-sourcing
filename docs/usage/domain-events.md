## Domain events

A domain event is a simple Data Transfer Object (or message), no business logic involved.
It typically describes something that already happened.

In _Gember Event Sourcing_, any DTO can be used as a domain event.

With DCB, a domain event is not limited to a single aggregate root model anymore.
Instead, the same event can be used across multiple business decision models.
To make this work, a domain event is linked to a set of **domain tags**.
You can define those by adding the `#[DomainTag]` attribute to the appropriate properties.

```php
final readonly class SomeEvent
{
    public function __construct(
        #[DomainTag]
        public string $id,
        public int $value,
    ) {}
}
```

In some cases, using this attribute isn't enough.
If you need more control, the event can implement the `SpecifiedDomainTags` interface instead.

```php
final readonly class SomeEvent implements SpecifiedDomainTags
{
    public function __construct(
        public string $id,
        public int $value,
    ) {}

    public function getDomainTags(): array
    {
        return [
            $this->id,
        ];
    }
}
```
Every domain event needs two things to be appended in the event store:

1. The event name
2. The event payload; the event needs to be serializable

### Domain event name
There are a few ways to set the name of a domain event:

1. Use `#[DomainEvent]` attribute
2. Implement the `NamedDomainEvent` interface

If neither of those is used, _Gember Event Sourcing_ will fall back to generating a name based on the event's FQCN (Fully Qualified Class Name)

```php
#[DomainEvent(name: 'some.event')]
final readonly class SomeEvent
{
    public function __construct(
        #[DomainTag]
        public string $id,
        public int $value,
    ) {}
}

final readonly class AnotherEvent implements NamedDomainEvent
{
    public function __construct(
        #[DomainTag]
        public string $id,
        public int $value,
    ) {}

    public static function getName(): string
    {
        return 'another.event';
    }
}
```

### Serialization

_Gember Event Sourcing_ includes a `Serializer` that can automatically handle serialization for domain events.
Therefore, it is important to make sure all properties of a domain event are serializable.
It's common practice to only use primitive types for all properties.

> Note: It can be tempting to use your domain Value Objects in your domain events, but that is usually not a good idea.
> Not only serialization can become difficult, but more importantly, using mutable Value Objects could lead to past events accidentally changing when the Value Objects evolves over time.
> That goes against the core idea of event sourcing, where events should be immutable once stored.

If you need to handle more complex serialization, you can implement the `Serializable` interface
and take full control over how the event is serialized and deserialized.

```php
/**
 * @implements Serializable<array{
 *    id: string,
 *    value: int
 * }, SomeEvent>
 */
#[DomainEvent(name: 'some.event')]
final readonly class SomeEvent implements Serializable
{
    public function __construct(
        #[DomainTag]
        public string $id,
        public int $value,
    ) {}

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['id'],
            $payload['value'],
        );
    }

    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
        ];
    }
}
```

### Examples

A simple example of a domain event related to multiple domain tags:

```php
#[DomainEvent(name: 'student-subscribed-to-course')]
final readonly class StudentSubscribedToCourseEvent
{
    public function __construct(
        #[DomainTag]
        public string $courseId,
        #[DomainTag]
        public string $studentId,
    ) {}
}
```

For more extended example check out the demo application [gember/example-event-sourcing-dcb](https://github.com/GemberPHP/example-event-sourcing-dcb).

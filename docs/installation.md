## Installation
Gember Event Sourcing is not tied to any framework (agnostic).
It can be used in any PHP project.

### Symfony
For Symfony (`^7.1` or `^8.0`) projects there is a bundle package to ease installation.
Further installation details see [gember/event-sourcing-symfony-bundle](https://github.com/GemberPHP/event-sourcing-symfony-bundle).

#### Setup
1. **Register the bundle.** Symfony Flex does this on install; without Flex, add `Gember\EventSourcingSymfonyBundle\GemberEventSourcingBundle::class => ['all' => true]` to `config/bundles.php`.
2. **Configure the message buses.** The bundle uses Symfony Messenger buses named `command.bus` and `event.bus`. Events without handlers are normal, so allow them on the event bus:

    ```yaml
    # config/packages/messenger.yaml
    framework:
        messenger:
            default_bus: command.bus
            buses:
                command.bus:
                event.bus:
                    default_middleware:
                        allow_no_handlers: true
    ```

3. **Create the database tables.** Copy the [Doctrine migrations](https://github.com/GemberPHP/rdbms-event-store-doctrine-dbal/tree/main/resources/migrations/doctrine) (namespace `DoctrineMigrations`) or the [Phinx migrations](https://github.com/GemberPHP/rdbms-event-store-doctrine-dbal/tree/main/resources/migrations/phinx) into your project and run them, or use [schema.sql](https://github.com/GemberPHP/rdbms-event-store-doctrine-dbal/blob/main/resources/schema.sql). When upgrading, see [Upgrading](upgrading.md).
4. **Register your use cases and sagas as services** with autoconfiguration (the default for `src/` in Symfony): `#[DomainCommandHandler]` and `#[SagaEventSubscriber]` methods are routed through it. Use cases or sagas excluded from service registration silently get no messages.

#### Supported databases
The event store, saga store, snapshot store and outbox run on MySQL and MariaDB (InnoDB) and on SQLite. `schema.sql` and the Doctrine migrations are written for MySQL/MariaDB.

### Without a framework
Compatible with any application or framework that supports [container-interop/service-provider](https://github.com/container-interop/service-provider),
there is a universal service provider.
Further installation details see [gember/event-sourcing-universal-service-provider](https://github.com/GemberPHP/event-sourcing-universal-service-provider).

## Dependencies
_Gember Event Sourcing_ depends on a few external libraries. To keep things flexible, each of these dependencies is kept separate from the core library and moved into its own add-on package. That way, you can easily swap out any dependency for another one you prefer.

**The following adapter packages are currently available:**

- [rdbms-event-store-doctrine-dbal](https://github.com/GemberPHP/rdbms-event-store-doctrine-dbal)
- [message-bus-symfony](https://github.com/GemberPHP/message-bus-symfony)
- [identity-generator-symfony](https://github.com/GemberPHP/identity-generator-symfony)
- [serializer-symfony](https://github.com/GemberPHP/serializer-symfony)

**Note:** When using the Symfony Bundle or the universal service provider, all dependencies packages are installed automatically.

## Static analysis
[gember/phpstan-gember](https://github.com/GemberPHP/phpstan-gember) is an optional PHPStan extension: it lets PHPStan know that private `#[DomainEventSubscriber]` methods are used and `#[DomainTag]` properties are read, so they aren't reported as unused.

## Caching

Resolver and registry classes use PHP reflection to scan attributes at runtime. Caching decorators avoid this overhead by storing resolved definitions in a PSR-16 cache.

### Available cache decorators

**Resolver caches** — cache the attribute metadata for individual classes:

| Decorator | Wraps | Cache key pattern |
|-----------|-------|-------------------|
| `CachedUseCaseResolverDecorator` | `UseCaseResolver` | `gember.resolver.use_case.{className}` |
| `CachedDomainEventResolverDecorator` | `DomainEventResolver` | `gember.resolver.domain_event.{className}` |
| `CachedDomainCommandResolverDecorator` | `DomainCommandResolver` | `gember.resolver.domain_command.{className}` |
| `CachedSagaResolverDecorator` | `SagaResolver` | `gember.resolver.saga.{className}` |

**Registry caches** — cache the lookup tables that map commands/events to their handlers:

| Decorator | Wraps | Cache key pattern |
|-----------|-------|-------------------|
| `CachedCommandHandlerRegistryDecorator` | `CommandHandlerRegistry` | `gember.registry.command_handler.{className}` |
| `CachedEventRegistryDecorator` | `EventRegistry` | `gember.registry.event.{eventName}` |
| `CachedSagaRegistryDecorator` | `SagaRegistry` | `gember.registry.saga.{sagaIdName}` |

### How caching works

All Definition DTOs implement the `Serializable` interface with `toPayload()` and `fromPayload()` methods. The cache decorators:

1. Check if a cache entry exists for the key
2. If **miss**: resolve via reflection, serialize the definition to JSON using `toPayload()`, store in cache
3. If **hit**: deserialize from JSON using `fromPayload()`, return the definition

This means the cache stores plain JSON strings — compatible with any PSR-16 cache backend (Redis, Memcached, APCu, filesystem, etc.).

### Symfony bundle configuration

When using the Symfony bundle, caching is configured in `gember_event_sourcing.yaml`:

```yaml
gember_event_sourcing:
    cache:
        enabled: true       # Enable/disable all cache decorators
        psr6:
            service: '@cache.app'  # PSR-6 cache pool (default)

        # Or use a PSR-16 compatible cache directly:
        # psr16:
        #     service: '@some.psr16.service'
```

Setting `enabled: true` activates all seven cache decorators at once. The bundle uses `cache.app` (Symfony's default PSR-6 cache pool, wrapped in `Psr16Cache`) by default.

### Cache invalidation

The cache decorators do not implement automatic invalidation. Since resolver and registry data is derived from class attributes (which only change during deployment), the recommended approach is to **clear the cache on deployment** — for example, via `bin/console cache:clear` in Symfony.

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

On a cache miss, the decorators resolve the definition via reflection and store it as a plain string (JSON); on a hit, they restore it from the cache. Any PSR-16 cache backend works (Redis, Memcached, APCu, filesystem, etc.).

### Symfony bundle configuration

When using the Symfony bundle, caching is configured in `gember_event_sourcing.yaml`:

```yaml
gember_event_sourcing:
    cache:
        enabled: true       # Enable/disable all cache decorators (default: false)
        psr6:
            service: '@cache.app'  # PSR-6 cache pool (default)

        # Or use a PSR-16 compatible cache directly:
        # psr16:
        #     service: '@some.psr16.service'
```

Setting `enabled: true` activates all seven cache decorators at once. The bundle uses `cache.app` (Symfony's default PSR-6 cache pool, wrapped in `Psr16Cache`) by default.

### Cache invalidation

The cache decorators do not implement automatic invalidation. Since resolver and registry data is derived from class attributes (which only change during deployment), the recommended approach is to **clear the cache on deployment**. In Symfony, `bin/console cache:clear` only clears a filesystem pool in `var/cache`: for `cache.app` on Redis, Memcached or APCu, run `bin/console cache:pool:clear cache.app` (or the pool you configured). A stale cache can, for example, keep a new saga subscriber from receiving events.

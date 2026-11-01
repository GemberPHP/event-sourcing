## Caching

Resolver and registry classes use PHP reflection to scan attributes at runtime. Caching decorators avoid this overhead by storing resolved definitions in a PSR-16 cache.

### Available cache decorators

**Resolver caches** — cache the attribute metadata for individual classes:

| Decorator | Wraps | Cache key |
|-----------|-------|-------------------|
| `CachedUseCaseResolverDecorator` | `UseCaseResolver` | `gember.resolver.use_case.{hash}` |
| `CachedDomainEventResolverDecorator` | `DomainEventResolver` | `gember.resolver.domain_event.{hash}` |
| `CachedDomainCommandResolverDecorator` | `DomainCommandResolver` | `gember.resolver.domain_command.{hash}` |
| `CachedSagaResolverDecorator` | `SagaResolver` | `gember.resolver.saga.{hash}` |

**Registry caches** — cache the lookup tables that map commands/events to their handlers:

| Decorator | Wraps | Cache key |
|-----------|-------|-------------------|
| `CachedCommandHandlerRegistryDecorator` | `CommandHandlerRegistry` | `gember.registry.command_handler.{hash}` |
| `CachedEventRegistryDecorator` | `EventRegistry` | `gember.registry.event.{hash}` |
| `CachedSagaRegistryDecorator` | `SagaRegistry` | `gember.registry.saga.{hash}` |

### How caching works

On a cache miss, the decorators resolve the definition via reflection and store it as a plain string (JSON); on a hit, they restore it from the cache. Any PSR-16 cache backend works (Redis, Memcached, APCu, filesystem, etc.).

The `{hash}` in a key is a hash of the class name, event name or saga ID name, together with the installed version of _Gember Event Sourcing_. Keys therefore only contain characters every PSR-16 implementation supports, within 64 characters, whatever characters an event name contains. Upgrading _Gember Event Sourcing_ starts with an empty cache, as the cached definitions can change between versions.

An entry that can't be read (e.g. corrupted, or an event class that no longer exists) is resolved again and overwritten, instead of failing.

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

The cache decorators do not invalidate entries when your code changes. Since resolver and registry data is derived from class attributes (which only change during deployment), the recommended approach is to **clear the cache on deployment**. In Symfony, `bin/console cache:clear` only clears a filesystem pool in `var/cache`: for `cache.app` on Redis, Memcached or APCu, run `bin/console cache:pool:clear cache.app` (or the pool you configured). A stale cache can, for example, keep a new saga subscriber from receiving events.

With a rolling deployment, old and new versions of your application run side by side, and clearing the cache doesn't help: both write to the same keys. Give each release its own cache namespace instead. In Symfony, set the cache prefix seed to a release identifier, which namespaces every cache pool per release:

```yaml
framework:
    cache:
        prefix_seed: '%env(APP_RELEASE)%'
```

Without Symfony, use a namespace per release in the PSR-16 cache you configure.

# Upgrading gember/event-sourcing

While in 0.x, a minor version (0.x.0) can contain breaking changes, and a patch version (0.x.y) only fixes bugs. Apply the notes of every version you pass, oldest first.

This file lists what you need to do or know when upgrading this package. For every change, see the [release notes](https://github.com/GemberPHP/event-sourcing/releases). Changes to the database migrations of `gember/rdbms-event-store-doctrine-dbal` are in its [UPGRADE.md](https://github.com/GemberPHP/rdbms-event-store-doctrine-dbal/blob/main/UPGRADE.md).

## 0.22.0

- **Cached resolvers and the cached command handler registry no longer take a `FriendlyClassNamer`.** If you wire them yourself (not through the Symfony bundle or the universal service provider), remove that argument from:
  - `CachedUseCaseResolverDecorator`
  - `CachedDomainEventResolverDecorator`
  - `CachedDomainCommandResolverDecorator`
  - `CachedSagaResolverDecorator`
  - `CachedCommandHandlerRegistryDecorator`
- **The cache starts empty once.** Cache keys changed, so after upgrading every resolver and registry lookup misses once and is resolved through reflection again. Entries under the previous keys stay in a persistent pool until they expire or you clear it (e.g. `bin/console cache:pool:clear cache.app`).
- **Each snapshotted use case replays all its events once.** Snapshots are now also identified by use case class and snapshot version, so existing snapshots are no longer used. The next save that meets a snapshot policy creates a new snapshot. Old rows stay in `snapshot_store`; truncating it is safe.
- **New: `#[Snapshot(version: n)]`** (default `1`). Bump it when you change how a use case builds its state from the same events, e.g. an event subscriber changed, or a state property with a default value was added. Otherwise a snapshot of the previous logic keeps being used. See [Snapshotting - Snapshot version](https://gemberphp.github.io/event-sourcing/usage/snapshotting/#snapshot-version).
- **Requires `composer-runtime-api ^2.0`**, which Composer 2 provides.

## 0.21.1

No API changes, but setups that were silently wrong now fail:

- A command whose domain tags differ from its use case's domain tags (in any order) throws an `InvalidUseCaseDomainTagsException`, and the use case is not saved.
- A command without domain tags fails loading its use case: a `UseCaseRepositoryFailedException`, caused by an `InvalidUseCaseDomainTagsException`.
- Two event classes with the same event name throw an `EventAlreadyRegisteredException`. A command handled by two use case methods throws a `CommandHandlerAlreadyRegisteredException`.
- Files without a class (e.g. only a trait or functions) in the scanned path are skipped instead of failing the scan.

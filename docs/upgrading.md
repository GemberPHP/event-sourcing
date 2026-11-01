## Upgrading

> Upgrading `gember/event-sourcing` itself? See [UPGRADE.md](https://github.com/GemberPHP/event-sourcing/blob/main/UPGRADE.md). This page covers the database migrations of `gember/rdbms-event-store-doctrine-dbal`.

While _Gember Event Sourcing_ is in its 0.x phase, the released database migrations of [gember/rdbms-event-store-doctrine-dbal](https://github.com/GemberPHP/rdbms-event-store-doctrine-dbal) are changed in place instead of adding new ones. A database that already ran an earlier version of a migration needs the change applied manually. Apply the steps for every version you pass, oldest first. Migrations copied into your application need the same changes.

Versions below are versions of `gember/rdbms-event-store-doctrine-dbal`, unless stated otherwise.

### 0.16.0: versioned snapshots

Snapshots are versioned (insert-only) instead of one per boundary. The `snapshot_store` table schema has changed:

- New `id` column (primary key, UUID)
- `boundary_hash` is no longer the primary key
- New unique constraint on `(boundary_hash, event_count)`
- `updated_at` column removed (rows are immutable)

**Drop and recreate the `snapshot_store` table** using the updated schema. Existing snapshots will be rebuilt automatically by the configured policies.

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

### 0.17.0: locks per domain tag and event type

Earlier versions locked a single row per exact boundary. Old and new versions lock different rows and therefore don't serialize against each other: don't run them side by side, deploy as a cut-over. The old lock rows are no longer used; they are harmless and can be deleted once no old version is running.

Since this version (and `gember/event-sourcing` 0.19.0) the optimistic lock compares the number of matching events. Snapshots created before this version could count events the use case doesn't subscribe to. They mostly fail the [snapshot verification](usage/snapshotting.md) and are replaced automatically, but it's recommended to truncate `snapshot_store` once after upgrading.

### 0.18.0: stale snapshot detection

The lock table got a `version` column, which every save reads and increases. A database that ran the migrations of 0.17 fails on every save until it's added:

```sql
-- MySQL, MariaDB and SQLite
ALTER TABLE event_store_lock ADD COLUMN version int NOT NULL DEFAULT 0;
```

Add it before deploying: the previous version ignores the column.

### 0.19.0: saga store revision

This applies to databases created with `gember/rdbms-event-store-doctrine-dbal` before 0.19.0. The saga store got a revision, a saga name per Saga ID with a unique key, and lost its lock table. The released migrations were changed in place, so **a database that already ran them needs these changes applied manually**. Old and new code can't run against each other's schema, so upgrade with everything stopped:

1. Take a backup.
2. Stop everything that handles saga events: outbox processors and message consumers, and in direct dispatch mode the whole application.
3. Run the pre-flight query below and resolve every row it returns.
4. Apply the upgrade SQL.
5. Remove the deleted migration from the migration history (see below).
6. Deploy the new version, then start everything again.

Saga ID values are now compared case-sensitively. On MySQL and MariaDB, looking up a saga ignored case before: an event with Saga ID `Order-1` found the saga stored with `order-1`. After the upgrade it doesn't (a new saga is created with `CreationPolicy::IfMissing`, the event is skipped with `Never`). Make sure events carry Saga ID values exactly as they were stored.

**Pre-flight: Saga ID values owned by more than one saga.** These block the unique key:

```sql
-- MySQL / MariaDB
SET SESSION group_concat_max_len = 1000000;

SELECT s.saga_name, MIN(r.saga_id) AS saga_id, COUNT(DISTINCT s.id) AS sagas,
       GROUP_CONCAT(CONCAT(s.id, ' (updated: ', COALESCE(s.updated_at, 'never'), ')')
                    ORDER BY s.updated_at DESC SEPARATOR ', ') AS saga_store_ids
FROM saga_store_relation r
JOIN saga_store s ON s.id = r.id
GROUP BY s.saga_name, r.saga_id COLLATE utf8mb4_bin
HAVING COUNT(DISTINCT s.id) > 1;

-- SQLite
SELECT s.saga_name, r.saga_id, COUNT(DISTINCT s.id) AS sagas,
       GROUP_CONCAT(s.id || ' (updated: ' || COALESCE(s.updated_at, 'never') || ')', ', ') AS saga_store_ids
FROM saga_store_relation r
JOIN saga_store s ON s.id = r.id
GROUP BY s.saga_name, r.saga_id
HAVING COUNT(DISTINCT s.id) > 1;
```

Each row is a Saga ID value (`saga_id`) shared by several sagas (`sagas`) of one saga name. Resolve it depending on its cause:

- **A Saga ID that isn't one-to-one with a saga** (such as a `customerId` on order sagas, see [Saga IDs](usage/sagas.md#saga-ids)): remove it from the saga class, and delete only the relation rows of that value. Don't delete these sagas: they are separate, live sagas. Relations of values that weren't shared are removed on the saga's next save.

    ```sql
    DELETE r FROM saga_store_relation r
    JOIN saga_store s ON s.id = r.id
    WHERE s.saga_name = 'order.fulfillment' AND r.saga_id = 'customer-1';
    ```

    (SQLite: `DELETE FROM saga_store_relation WHERE saga_id = 'customer-1' AND id IN (SELECT id FROM saga_store WHERE saga_name = 'order.fulfillment');`)

- **The same saga created twice** (by concurrent events in earlier versions): keep the one that was updated most recently (see `updated`: it received the later events), and delete the others, first their rows in `saga_store_relation`, then in `saga_store`. If a deleted saga has Saga ID values the kept one lacks, move them first: `UPDATE saga_store_relation SET id = '<kept id>' WHERE id = '<deleted id>' AND saga_id = '<value>';`.

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

-- Only for databases created with gember/rdbms-event-store-doctrine-dbal 0.13.0 or later
-- (fails with error 1091 otherwise, which is fine)
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

**Deleted migration.** The migration that created `saga_store_lock` (Doctrine `Version20260818130000`, Phinx `20260818130000`, released in `gember/rdbms-event-store-doctrine-dbal` 0.13.0) no longer exists. Remove it from the migration history (otherwise Doctrine Migrations warns about an executed unavailable migration):

```bash
bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260818130000' --delete --no-interaction
```

For Phinx: `DELETE FROM phinxlog WHERE version = 20260818130000;`. Migrations copied into your application need the same changes.

**Rolling back** to the previous version after the upgrade (MySQL / MariaDB):

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

ALTER TABLE saga_store DROP COLUMN revision;
```

Then, before the previous version runs its migrations, put the deleted migration back in the history, as its table exists again:

```bash
bin/console doctrine:migrations:version 'DoctrineMigrations\Version20260818130000' --add --no-interaction
```

For Phinx: `INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint) VALUES (20260818130000, 'V20260818130000', NOW(), NOW(), 0);`. The upgrade can be applied again later as described above.

### 0.20.0: case-sensitive domain tags

Before `gember/rdbms-event-store-doctrine-dbal` 0.20.0, MySQL and MariaDB compared domain tags ignoring case when loading and counting events, while the locks compared them exactly: writers using `course-1` and `COURSE-1` read each other's events without locking each other out. The released migrations were changed in place, so a database that already ran them needs this change applied manually (it rebuilds the table: about 3 seconds per 500,000 tag rows):

```sql
ALTER TABLE event_store_relation MODIFY domain_tag varchar(100) COLLATE utf8mb4_bin NOT NULL;
```

Tags that differ only in case become separate streams. If your application relied on those matching, normalize the identifiers before upgrading. SQLite already compared tags exactly.

### 0.21.0: claimed outbox messages

Before `gember/rdbms-event-store-doctrine-dbal` 0.21.0, concurrent processors dispatched the same messages (the `SKIP LOCKED` locks were released right away). The released migrations were changed in place, so a database that already ran them needs the two columns added manually:

```sql
-- Fail instead of waiting long for a metadata lock behind long-running transactions on the outbox
SET SESSION lock_wait_timeout = 5;

ALTER TABLE outbox
  ADD COLUMN claimed_by varchar(100) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  ADD COLUMN claimed_until datetime(6) NULL DEFAULT NULL,
  ALGORITHM=INSTANT;
```

This is instant on MySQL 8.0.12+ and MariaDB 10.3+ (no table rebuild; `ALGORITHM=INSTANT` makes it fail instead of rebuilding the table otherwise). The previous version ignores the new columns, so add them before deploying; a rolling deploy works. Stop processors of the previous version before starting new ones: running side by side, the old ones still dispatch messages the new ones claimed.

SQLite:

```sql
ALTER TABLE outbox ADD COLUMN claimed_by varchar(100) NULL DEFAULT NULL;
ALTER TABLE outbox ADD COLUMN claimed_until datetime(6) NULL DEFAULT NULL;
```

### 0.22.0: outbox retries with a backoff

Since `gember/rdbms-event-store-doctrine-dbal` 0.22.0, a failed message is retried after a backoff instead of on the next poll, and on MySQL and MariaDB it gets one more attempt than before: dead-lettering now happens after the first attempt and `max_retries` retries on every database. Messages waiting for a retry are skipped through the `outbox_unprocessed` index, which now includes `claimed_until` (changed in place in the released migrations). Existing databases:

```sql
SET SESSION lock_wait_timeout = 5;

ALTER TABLE outbox
  DROP INDEX outbox_unprocessed,
  ADD INDEX outbox_unprocessed (processed_at, dead_lettered_at, claimed_until, created_at, id),
  ALGORITHM=INPLACE, LOCK=NONE;
```

This rebuilds the index online (about 4 seconds per 1.5 million rows), without blocking writes. Version 0.21 works with either index, so run it before or after deploying. SQLite (when the index exists):

```sql
DROP INDEX outbox_unprocessed;
CREATE INDEX outbox_unprocessed ON outbox (processed_at, dead_lettered_at, claimed_until, created_at, id);
```

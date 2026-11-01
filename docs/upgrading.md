## Upgrading

Upgrade steps are listed per package, newest version first. Apply the steps of every version you pass, oldest first.

- **[gember/event-sourcing](https://github.com/GemberPHP/event-sourcing/blob/main/UPGRADE.md)**: what to do or know when upgrading the library itself. While in 0.x, a minor version can contain breaking changes, and a patch version only fixes bugs.
- **[gember/rdbms-event-store-doctrine-dbal](https://github.com/GemberPHP/rdbms-event-store-doctrine-dbal/blob/main/UPGRADE.md)**: the manual steps for a database that already ran an earlier version of a migration. While in 0.x, its released migrations are changed in place instead of adding new ones.

The Symfony bundle and the universal service provider follow these versions: upgrading them pulls in the versions above.

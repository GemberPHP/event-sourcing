<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Cache;

use Composer\InstalledVersions;
use OutOfBoundsException;

/**
 * Creates the cache keys of the cached resolvers and registries.
 *
 * The identifier (a class name, event name or saga id name) is hashed, so a key only contains characters every
 * PSR-16 implementation supports (A-Z, a-z, 0-9, _ and .) and stays within its guaranteed 64 characters.
 *
 * The installed version of this library is part of the hash: an upgrade can change the cached payloads, so an
 * upgrade starts with an empty cache instead of reading payloads of the previous version.
 */
final class CacheKey
{
    private const string PACKAGE_NAME = 'gember/event-sourcing';

    private static ?string $libraryVersion = null;

    public static function create(string $type, string $identifier): string
    {
        return sprintf('gember.%s.%s', $type, hash('xxh128', self::getLibraryVersion() . "\0" . $identifier));
    }

    private static function getLibraryVersion(): string
    {
        if (self::$libraryVersion !== null) {
            return self::$libraryVersion;
        }

        try {
            $libraryVersion = sprintf(
                '%s@%s',
                InstalledVersions::getVersion(self::PACKAGE_NAME) ?? '',
                InstalledVersions::getReference(self::PACKAGE_NAME) ?? '',
            );
        } catch (OutOfBoundsException) {
            // Not installed with Composer
            $libraryVersion = 'unknown';
        }

        return self::$libraryVersion = $libraryVersion;
    }
}

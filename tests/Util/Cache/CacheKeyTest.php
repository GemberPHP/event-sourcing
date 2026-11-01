<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Cache;

use Gember\EventSourcing\Util\Cache\CacheKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CacheKeyTest extends TestCase
{
    #[Test]
    public function itShouldCreateKeySupportedByEveryPsr16Implementation(): void
    {
        $cacheKey = CacheKey::create(
            'registry.command_handler',
            'billing:invoice/paid{v2}@' . str_repeat('very-long-identifier.', 10),
        );

        self::assertMatchesRegularExpression('/^[A-Za-z0-9_.]+$/', $cacheKey);
        self::assertLessThanOrEqual(64, strlen($cacheKey));
        self::assertStringStartsWith('gember.registry.command_handler.', $cacheKey);
    }

    #[Test]
    public function itShouldCreateSameKeyForSameTypeAndIdentifier(): void
    {
        self::assertSame(
            CacheKey::create('registry.event', 'course.created'),
            CacheKey::create('registry.event', 'course.created'),
        );
    }

    #[Test]
    public function itShouldCreateDifferentKeysForDifferentIdentifiers(): void
    {
        self::assertNotSame(
            CacheKey::create('registry.event', 'course.created'),
            CacheKey::create('registry.event', 'course.removed'),
        );
    }
}

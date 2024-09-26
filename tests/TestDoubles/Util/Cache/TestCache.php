<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\Cache;

use DateInterval;
use Gember\EventSourcing\Util\Cache\Cache;

/**
 * @template T of mixed
 *
 * @implements Cache<T>
 */
final class TestCache implements Cache
{
    /**
     * @var array<string, mixed>
     */
    private array $cache = [];

    public function get(string $key): mixed
    {
        return $this->cache[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->cache);
    }

    public function set(string $key, mixed $data, ?DateInterval $timeToLive = null): void
    {
        $this->cache[$key] = $data;
    }
}

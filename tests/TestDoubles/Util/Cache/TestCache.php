<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final class TestCache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $cache = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache[$key] ?? null;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->cache[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->cache[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->cache = [];

        return true;
    }

    /**
     * @param iterable<string> $keys
     *
     * @return iterable<mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $this->get($key, $default);
        }
    }

    /**
     * @param iterable<array<string, mixed>> $values
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->cache);
    }
}

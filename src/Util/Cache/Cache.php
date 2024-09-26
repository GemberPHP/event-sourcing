<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Cache;

use DateInterval;

/**
 * @template T of mixed
 */
interface Cache
{
    /**
     * @throws CacheException
     *
     * @return T
     */
    public function get(string $key): mixed;

    /**
     * @throws CacheException
     */
    public function has(string $key): bool;

    /**
     * @param T $data
     *
     * @throws CacheException
     */
    public function set(string $key, mixed $data, ?DateInterval $timeToLive = null): void;
}

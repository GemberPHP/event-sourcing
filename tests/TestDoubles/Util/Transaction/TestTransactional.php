<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\Transaction;

use Gember\DependencyContracts\Util\Transaction\Transactional;

/**
 * @internal
 */
final class TestTransactional implements Transactional
{
    public bool $wasCalled = false;

    public function transactional(callable $operation): mixed
    {
        $this->wasCalled = true;

        return $operation();
    }
}

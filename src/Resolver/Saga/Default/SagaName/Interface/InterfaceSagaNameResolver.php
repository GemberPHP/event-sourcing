<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga\Default\SagaName\Interface;

use Gember\EventSourcing\Resolver\Saga\Default\SagaName\SagaNameResolver;
use Gember\EventSourcing\Resolver\Saga\Default\SagaName\UnresolvableSagaNameException;
use Gember\EventSourcing\Saga\NamedSaga;
use Override;

/**
 * Resolve saga name by interface NamedSaga.
 */
final readonly class InterfaceSagaNameResolver implements SagaNameResolver
{
    #[Override]
    public function resolve(string $sagaClassName): string
    {
        if (!is_subclass_of($sagaClassName, NamedSaga::class)) {
            throw UnresolvableSagaNameException::create(
                $sagaClassName,
                'Saga does not implement NamedSaga interface',
            );
        }

        /* @var class-string<NamedSaga> $sagaClassName */
        return $sagaClassName::getName();
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Saga\Default\SagaName\ClassName;

use Gember\EventSourcing\Resolver\Saga\Default\SagaName\SagaNameResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\FriendlyClassNamer;
use Override;

/**
 * Fallback resolver, to generate a saga name based on the FQCN.
 */
final readonly class ClassNameSagaNameResolver implements SagaNameResolver
{
    public function __construct(
        private FriendlyClassNamer $friendlyClassNamer,
    ) {}

    #[Override]
    public function resolve(string $sagaClassName): string
    {
        return $this->friendlyClassNamer->createFriendlyClassName($sagaClassName);
    }
}

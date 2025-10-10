<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Common\SagaId;

use Stringable;

final readonly class SagaIdValueHelper
{
    public static function getSagaIdValue(object $object, SagaIdDefinition $sagaIdDefinition): string|Stringable
    {
        /** @var string|Stringable */
        return $object->{$sagaIdDefinition->sagaIdName};
    }
}

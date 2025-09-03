<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\Serialization\Serializer;

use Gember\DependencyContracts\Util\Serialization\Serializer\Serializer;
use Override;
use stdClass;

final readonly class TestSerializer implements Serializer
{
    #[Override]
    public function serialize(object $object): string
    {
        return serialize($object);
    }

    #[Override]
    public function deserialize(string $payload, string $className): object
    {
        return new stdClass();
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Common\DomainTag;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class DomainTagDefinitionTest extends TestCase
{
    #[Test]
    public function itShouldSerializeAndDeserialize(): void
    {
        $definition = new DomainTagDefinition('name', DomainTagType::Property);

        $serialized = $definition->toPayload();

        self::assertSame([
            'domainTagName' => 'name',
            'type' => 'property',
        ], $serialized);

        $deserialized = DomainTagDefinition::fromPayload($serialized);

        self::assertEquals($deserialized, $definition);
    }
}

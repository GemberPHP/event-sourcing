<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\DomainCommand;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseWithCommand;
use PHPUnit\Framework\TestCase;
use Gember\EventSourcing\Resolver\DomainCommand\DomainCommandDefinition;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class DomainCommandDefinitionTest extends TestCase
{
    #[Test]
    public function itShouldSerializeAndDeserialize(): void
    {
        $definition = new DomainCommandDefinition(
            TestUseCaseWithCommand::class,
            [
                new DomainTagDefinition('domainTag', DomainTagType::Property),
            ],
        );

        $serialized = $definition->toPayload();

        self::assertSame([
            'commandClassName' => TestUseCaseWithCommand::class,
            'domainTags' => [
                [
                    'domainTagName' => 'domainTag',
                    'type' => 'property',
                ],
            ],
        ], $serialized);

        $deserialized = DomainCommandDefinition::fromPayload($serialized);

        self::assertEquals($deserialized, $definition);
    }
}

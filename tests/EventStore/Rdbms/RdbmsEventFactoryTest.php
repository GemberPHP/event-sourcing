<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\EventStore\Rdbms;

use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\Metadata;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsEvent;
use Gember\EventSourcing\EventStore\Rdbms\RdbmsEventFactory;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\Attribute\AttributeNormalizedEventNameResolver;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use Gember\EventSourcing\Test\TestDoubles\Util\Serialization\Serializer\TestSerializer;
use Gember\EventSourcing\Util\Attribute\Resolver\Reflector\ReflectorAttributeResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;
use DateTimeImmutable;

/**
 * @internal
 */
final class RdbmsEventFactoryTest extends TestCase
{
    private RdbmsEventFactory $factory;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new RdbmsEventFactory(
            new AttributeNormalizedEventNameResolver(new ReflectorAttributeResolver()),
            new TestSerializer(),
        );
    }

    #[Test]
    public function itShouldCreateFromEnvelope(): void
    {
        $event = $this->factory->createFromDomainEventEnvelope(new DomainEventEnvelope(
            'dc06d424-6bbb-4d22-ba58-1f76407e7286',
            [
                '8f9a8c7c-c776-4781-93a8-7cb9ff50db29',
            ],
            new TestUseCaseCreatedEvent('c739e791-c70c-47ad-bf87-32fec2bccd34', '10f3f8aa-100b-4b33-83f1-b934386de277'),
            new Metadata(['some' => 'data']),
            $appliedAt = new DateTimeImmutable(),
        ));

        self::assertEquals(new RdbmsEvent(
            'dc06d424-6bbb-4d22-ba58-1f76407e7286',
            [
                '8f9a8c7c-c776-4781-93a8-7cb9ff50db29',
            ],
            'test.use-case.created',
            'O:69:"Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent":2:{s:2:"id";s:36:"c739e791-c70c-47ad-bf87-32fec2bccd34";s:11:"secondaryId";s:36:"10f3f8aa-100b-4b33-83f1-b934386de277";}',
            ['some' => 'data'],
            $appliedAt,
        ), $event);
    }
}

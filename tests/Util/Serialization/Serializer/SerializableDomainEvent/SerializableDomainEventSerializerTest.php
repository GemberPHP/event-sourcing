<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Serialization\Serializer\SerializableDomainEvent;

use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestSerializableDomainEvent;
use Gember\EventSourcing\Test\TestDoubles\DomainContext\TestDomainContextCreatedEvent;
use Gember\EventSourcing\Util\Serialization\Serializer\SerializableDomainEvent\SerializableDomainEventSerializer;
use Gember\EventSourcing\Util\Serialization\Serializer\SerializationFailedException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SerializableDomainEventSerializerTest extends TestCase
{
    private SerializableDomainEventSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = new SerializableDomainEventSerializer();
    }

    #[Test]
    public function itShouldSerializeEventWithImplementedInterface(): void
    {
        $serialized = $this->serializer->serialize(new TestSerializableDomainEvent(
            'cf42cb77-34e1-494a-a2ce-e4ebe9c89838',
        ));

        self::assertSame('{"id":"cf42cb77-34e1-494a-a2ce-e4ebe9c89838"}', $serialized);
    }

    #[Test]
    public function itShouldThrowExceptionOnSerializeWhenEventDoesNotImplementInterface(): void
    {
        self::expectException(SerializationFailedException::class);
        self::expectExceptionMessage('Missing SerializableDomainEvent interface');

        $this->serializer->serialize(new TestDomainContextCreatedEvent(
            'cf42cb77-34e1-494a-a2ce-e4ebe9c89838',
            '330cfda7-3d0b-4802-b8e5-cc6a4166634b',
        ));
    }

    #[Test]
    public function itShouldDeserializeEventWithImplementedInterface(): void
    {
        $deserialized = $this->serializer->deserialize(
            '{"id":"cf42cb77-34e1-494a-a2ce-e4ebe9c89838"}',
            TestSerializableDomainEvent::class,
        );

        self::assertInstanceOf(TestSerializableDomainEvent::class, $deserialized);
        self::assertSame('cf42cb77-34e1-494a-a2ce-e4ebe9c89838', $deserialized->id);
    }

    #[Test]
    public function itShouldThrowExceptionOnDeserializeWhenEventDoesNotImplementInterface(): void
    {
        self::expectException(SerializationFailedException::class);
        self::expectExceptionMessage('Missing SerializableDomainEvent interface');

        $this->serializer->deserialize('{"data":"serialized"}', TestDomainContextCreatedEvent::class);
    }

    #[Test]
    public function itShouldThrowExceptionOnDeserializeWhenPayloadIsInvalid(): void
    {
        self::expectException(SerializationFailedException::class);
        self::expectExceptionMessage('State mismatch (invalid or malformed JSON)');

        $this->serializer->deserialize('{]INVALID', TestSerializableDomainEvent::class);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\Serialization\Serializer\Stacked;

use Gember\EventSourcing\Test\TestDoubles\UseCase\TestSerializableDomainEvent;
use Gember\EventSourcing\Test\TestDoubles\Util\Serialization\Serializer\TestThrowingExceptionSerializer;
use Gember\EventSourcing\Util\Serialization\Serializer\SerializableDomainEvent\SerializableDomainEventSerializer;
use Gember\EventSourcing\Util\Serialization\Serializer\SerializationFailedCollectionException;
use Gember\EventSourcing\Util\Serialization\Serializer\SerializationFailedException;
use Gember\EventSourcing\Util\Serialization\Serializer\Stacked\StackedSerializer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class StackedSerializerTest extends TestCase
{
    #[Test]
    public function itShouldSerialize(): void
    {
        $serializer  = new StackedSerializer([
            new TestThrowingExceptionSerializer(),
            new SerializableDomainEventSerializer(),
        ]);

        $serialized = $serializer->serialize(new TestSerializableDomainEvent('eccfd8da-1925-4365-8479-6ca189e5abf4'));

        self::assertSame('{"id":"eccfd8da-1925-4365-8479-6ca189e5abf4"}', $serialized);
    }

    #[Test]
    public function itShouldThrowExceptionWhenAllSerializersFailSerializing(): void
    {
        $serializer  = new StackedSerializer([
            new TestThrowingExceptionSerializer($exception1 = SerializationFailedException::withMessage('It failed')),
            new TestThrowingExceptionSerializer($exception2 = SerializationFailedException::withMessage('It failed')),
        ]);

        self::expectException(SerializationFailedCollectionException::class);
        self::expectExceptionMessage('All serializers failed to serialize');

        try {
            $serializer->serialize(new stdClass());
        } catch (SerializationFailedCollectionException $exception) {
            self::assertSame([$exception1, $exception2], $exception->getExceptions());

            throw $exception;
        }
    }

    #[Test]
    public function itShouldDeserialize(): void
    {
        $serializer  = new StackedSerializer([
            new TestThrowingExceptionSerializer(),
            new SerializableDomainEventSerializer(),
        ]);

        $deserialized = $serializer->deserialize(
            '{"id":"eccfd8da-1925-4365-8479-6ca189e5abf4"}',
            TestSerializableDomainEvent::class,
        );

        self::assertEquals(new TestSerializableDomainEvent('eccfd8da-1925-4365-8479-6ca189e5abf4'), $deserialized);
    }

    #[Test]
    public function itShouldThrowExceptionWhenAllSerializersFailDeserializing(): void
    {
        $serializer  = new StackedSerializer([
            new TestThrowingExceptionSerializer($exception1 = SerializationFailedException::withMessage('It failed')),
            new TestThrowingExceptionSerializer($exception2 = SerializationFailedException::withMessage('It failed')),
        ]);

        self::expectException(SerializationFailedCollectionException::class);
        self::expectExceptionMessage('All serializers failed to deserialize');

        try {
            $serializer->deserialize('deserialized payload', stdClass::class);
        } catch (SerializationFailedCollectionException $exception) {
            self::assertSame([$exception1, $exception2], $exception->getExceptions());

            throw $exception;
        }
    }
}

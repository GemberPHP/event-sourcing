<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\DomainContext;

use Gember\EventSourcing\DomainContext\Metadata;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class MetadataTest extends TestCase
{
    #[Test]
    public function itShouldAddMetadataImmutable(): void
    {
        $metadata = new Metadata(['foo' => 'bar', 'bar' => 'baz']);

        $updatedMetadata = $metadata
            ->withMetadata('foo', 'bar.updated')
            ->withMetadata('qux', 'fred');

        self::assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
        ], $metadata->metadata);

        self::assertSame([
            'foo' => 'bar.updated',
            'bar' => 'baz',
            'qux' => 'fred',
        ], $updatedMetadata->metadata);
    }

    #[Test]
    public function itShouldIterate(): void
    {
        $metadata = new Metadata(['foo' => 'bar', 'bar' => 'baz']);

        $data = [];
        foreach ($metadata as $key => $value) {
            $data[$key] = $value;
        }

        self::assertSame(['foo' => 'bar', 'bar' => 'baz'], $data);
    }
}

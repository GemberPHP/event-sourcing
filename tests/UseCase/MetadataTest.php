<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\UseCase;

use Gember\EventSourcing\UseCase\Metadata;
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

        $updatedMetadata = $metadata->addMetadata(['foo' => 'bar.updated', 'qux' => 'fred']);

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

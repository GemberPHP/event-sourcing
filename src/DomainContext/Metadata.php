<?php

declare(strict_types=1);

namespace Gember\EventSourcing\DomainContext;

use IteratorAggregate;
use ArrayIterator;
use Override;
use Traversable;

/**
 * @implements IteratorAggregate<string, mixed>
 */
final readonly class Metadata implements IteratorAggregate
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public array $metadata = [],
    ) {}

    /**
     * @param array<string, mixed> $metadata
     */
    public function addMetadata(array $metadata): self
    {
        return new self([
            ...$this->metadata,
            ...$metadata,
        ]);
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->metadata);
    }
}

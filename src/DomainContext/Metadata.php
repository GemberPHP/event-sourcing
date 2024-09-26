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

    public function withMetadata(string $key, mixed $value): self
    {
        return new self([
            ...$this->metadata,
            ...[$key => $value],
        ]);
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->metadata);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Attribute\Resolver;

use JsonSerializable;
use Override;

/**
 * @phpstan-type ParameterPayload array{name: string, type: class-string|null}
 */
final readonly class Parameter implements JsonSerializable
{
    /**
     * @param class-string|null $type
     */
    public function __construct(
        public string $name,
        public ?string $type,
    ) {}

    /**
     * @param ParameterPayload $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self($payload['name'], $payload['type']);
    }

    /**
     * @return ParameterPayload
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
        ];
    }
}

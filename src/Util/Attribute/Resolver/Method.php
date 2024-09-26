<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\Attribute\Resolver;

use JsonSerializable;
use Override;

/**
 * @phpstan-import-type ParameterPayload from Parameter
 *
 * @phpstan-type MethodPayload array{name: string, parameters: list<ParameterPayload>}
 */
final readonly class Method implements JsonSerializable
{
    /**
     * @param list<Parameter> $parameters
     */
    public function __construct(
        public string $name,
        public array $parameters,
    ) {}

    /**
     * @param MethodPayload $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self($payload['name'], array_map(fn($item) => Parameter::fromArray($item), $payload['parameters']));
    }

    /**
     * @return MethodPayload
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'parameters' => array_map(fn($parameter) => $parameter->jsonSerialize(), $this->parameters),
        ];
    }
}

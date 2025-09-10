<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainCommand;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @phpstan-import-type DomainTagDefinitionPayload from DomainTagDefinition
 *
 * @phpstan-type DomainCommandDefinitionPayload array{
 *     commandClassName: class-string,
 *     domainTags: list<DomainTagDefinitionPayload>
 * }
 *
 * @implements Serializable<DomainCommandDefinitionPayload, DomainCommandDefinition>
 */
final readonly class DomainCommandDefinition implements Serializable
{
    /**
     * @param class-string $commandClassName
     * @param list<DomainTagDefinition> $domainTags
     */
    public function __construct(
        public string $commandClassName,
        public array $domainTags,
    ) {}

    public function toPayload(): array
    {
        return [
            'commandClassName' => $this->commandClassName,
            'domainTags' => array_map(fn($domainTag) => $domainTag->toPayload(), $this->domainTags),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['commandClassName'],
            array_map(fn($domainTagPayload) => DomainTagDefinition::fromPayload($domainTagPayload), $payload['domainTags']),
        );
    }
}

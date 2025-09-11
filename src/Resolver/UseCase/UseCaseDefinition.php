<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Util\Serialization\Serializable;

/**
 * @phpstan-import-type DomainTagDefinitionPayload from DomainTagDefinition
 * @phpstan-import-type CommandHandlerDefinitionPayload from CommandHandlerDefinition
 * @phpstan-import-type EventSubscriberDefinitionPayload from EventSubscriberDefinition
 *
 * @phpstan-type UseCaseDefinitionPayload array{
 *     useCaseClassName: class-string,
 *     domainTags: list<DomainTagDefinitionPayload>,
 *     commandHandlers: list<CommandHandlerDefinitionPayload>,
 *     eventSubscribers: list<EventSubscriberDefinitionPayload>
 * }
 *
 * @implements Serializable<UseCaseDefinitionPayload, UseCaseDefinition>
 */
final readonly class UseCaseDefinition implements Serializable
{
    /**
     * @param class-string $useCaseClassName
     * @param list<DomainTagDefinition> $domainTags
     * @param list<CommandHandlerDefinition> $commandHandlers
     * @param list<EventSubscriberDefinition> $eventSubscribers
     */
    public function __construct(
        public string $useCaseClassName,
        public array $domainTags,
        public array $commandHandlers,
        public array $eventSubscribers,
    ) {}

    public function toPayload(): array
    {
        return [
            'useCaseClassName' => $this->useCaseClassName,
            'domainTags' => array_map(fn($domainTag) => $domainTag->toPayload(), $this->domainTags),
            'commandHandlers' => array_map(fn($commandHandler) => $commandHandler->toPayload(), $this->commandHandlers),
            'eventSubscribers' => array_map(fn($eventSubscriber) => $eventSubscriber->toPayload(), $this->eventSubscribers),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            $payload['useCaseClassName'],
            array_map(fn($domainTagPayload) => DomainTagDefinition::fromPayload($domainTagPayload), $payload['domainTags']),
            array_map(fn($commandHandlerPayload) => CommandHandlerDefinition::fromPayload($commandHandlerPayload), $payload['commandHandlers']),
            array_map(fn($eventSubscriberPayload) => EventSubscriberDefinition::fromPayload($eventSubscriberPayload), $payload['eventSubscribers']),
        );
    }
}

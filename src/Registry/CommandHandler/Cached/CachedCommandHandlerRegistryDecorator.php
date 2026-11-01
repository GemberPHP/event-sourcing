<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\CommandHandler\Cached;

use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerNotRegisteredException;
use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerRegistry;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\Util\Cache\CacheKey;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\FriendlyClassNamer;
use Psr\SimpleCache\CacheInterface;
use Override;
use JsonException;
use TypeError;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @phpstan-import-type CommandHandlerDefinitionPayload from CommandHandlerDefinition
 */
final readonly class CachedCommandHandlerRegistryDecorator implements CommandHandlerRegistry
{
    private const string CACHE_TYPE = 'registry.command_handler';

    public function __construct(
        private CommandHandlerRegistry $commandHandlerRegistry,
        private CacheInterface $cache,
        private FriendlyClassNamer $friendlyClassNamer,
    ) {}

    /**
     * @throws CommandHandlerNotRegisteredException
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    #[Override]
    public function retrieve(string $commandName): array
    {
        $cacheKey = $this->createCacheKey($commandName);
        $cachedDefinition = $this->cache->get($cacheKey);

        if (is_string($cachedDefinition)) {
            try {
                /** @var array{
                 *     useCaseClassName: class-string<EventSourcedUseCase>,
                 *     commandHandlerDefinition: CommandHandlerDefinitionPayload
                 * } $payload
                 */
                $payload = json_decode($cachedDefinition, true, flags: JSON_THROW_ON_ERROR);

                return [$payload['useCaseClassName'], CommandHandlerDefinition::fromPayload($payload['commandHandlerDefinition'])];
            } catch (JsonException|TypeError) {
                // Unreadable entry: retrieve it again and overwrite it
            }
        }

        [$useCaseClassName, $definition] = $this->commandHandlerRegistry->retrieve($commandName);

        $this->cache->set($cacheKey, json_encode([
            'useCaseClassName' => $useCaseClassName,
            'commandHandlerDefinition' => $definition->toPayload(),
        ], JSON_THROW_ON_ERROR));

        return [$useCaseClassName, $definition];
    }

    /**
     * @param class-string $commandName
     */
    private function createCacheKey(string $commandName): string
    {
        return CacheKey::create(self::CACHE_TYPE, $this->friendlyClassNamer->createFriendlyClassName($commandName));
    }
}

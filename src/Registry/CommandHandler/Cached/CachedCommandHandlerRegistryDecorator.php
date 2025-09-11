<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\CommandHandler\Cached;

use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerNotRegisteredException;
use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerRegistry;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\FriendlyClassNamer;
use Psr\SimpleCache\CacheInterface;
use Override;
use JsonException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @phpstan-import-type CommandHandlerDefinitionPayload from CommandHandlerDefinition
 */
final readonly class CachedCommandHandlerRegistryDecorator implements CommandHandlerRegistry
{
    private const string CACHE_KEY = 'gember.registry.command_handler.%s';

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

        if (!$this->cache->has($cacheKey)) {
            [$useCaseClassName, $definition] = $this->commandHandlerRegistry->retrieve($commandName);

            $this->cache->set($cacheKey, json_encode([
                'useCaseClassName' => $useCaseClassName,
                'commandHandlerDefinition' => $definition->toPayload(),
            ], JSON_THROW_ON_ERROR));

            return [$useCaseClassName, $definition];
        }

        /** @var string $cachedDefinition */
        $cachedDefinition = $this->cache->get($cacheKey);

        /** @var array{
         *     useCaseClassName: class-string<EventSourcedUseCase>,
         *     commandHandlerDefinition: CommandHandlerDefinitionPayload
         * } $payload
         */
        $payload = json_decode($cachedDefinition, true, flags: JSON_THROW_ON_ERROR);

        return [$payload['useCaseClassName'], CommandHandlerDefinition::fromPayload($payload['commandHandlerDefinition'])];
    }

    /**
     * @param class-string $commandName
     */
    private function createCacheKey(string $commandName): string
    {
        return sprintf(self::CACHE_KEY, $this->friendlyClassNamer->createFriendlyClassName($commandName));
    }
}

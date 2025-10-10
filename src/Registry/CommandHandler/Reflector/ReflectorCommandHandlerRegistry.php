<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\CommandHandler\Reflector;

use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerNotRegisteredException;
use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerRegistry;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\Resolver\UseCase\UseCaseResolver;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\Util\File\Finder\Finder;
use Gember\EventSourcing\Util\File\Reflector\Reflector;
use Override;

final class ReflectorCommandHandlerRegistry implements CommandHandlerRegistry
{
    /**
     * @var array<class-string, array{class-string<EventSourcedUseCase>, CommandHandlerDefinition}>
     */
    private array $definitions = [];

    public function __construct(
        private readonly Finder $finder,
        private readonly Reflector $reflector,
        private readonly UseCaseResolver $useCaseResolver,
        private readonly string $path,
    ) {}

    #[Override]
    public function retrieve(string $commandName): array
    {
        $this->initialize();

        if (!array_key_exists($commandName, $this->definitions)) {
            throw CommandHandlerNotRegisteredException::withCommandName($commandName);
        }

        return $this->definitions[$commandName];
    }

    private function initialize(): void
    {
        if ($this->definitions !== []) {
            return;
        }

        $files = $this->finder->getFiles($this->path);

        foreach ($files as $file) {
            if ($file === '') {
                continue;
            }

            $reflectionClass = $this->reflector->reflectClassFromFile($file);

            if (!$reflectionClass->implementsInterface(EventSourcedUseCase::class)) {
                continue;
            }

            /** @var class-string<EventSourcedUseCase> $useCaseClassName */
            $useCaseClassName = $reflectionClass->getName();

            $useCaseDefinition = $this->useCaseResolver->resolve($useCaseClassName);

            foreach ($useCaseDefinition->commandHandlers as $commandHandlerDefinition) {
                $this->definitions[$commandHandlerDefinition->commandClassName] = [
                    $useCaseClassName,
                    $commandHandlerDefinition,
                ];
            }
        }
    }
}

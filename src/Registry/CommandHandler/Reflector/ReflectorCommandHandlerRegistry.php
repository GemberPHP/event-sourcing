<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\CommandHandler\Reflector;

use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerAlreadyRegisteredException;
use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerNotRegisteredException;
use Gember\EventSourcing\Registry\CommandHandler\CommandHandlerRegistry;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\Resolver\UseCase\UseCaseResolver;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Gember\EventSourcing\Util\File\Finder\Finder;
use Gember\EventSourcing\Util\File\Reflector\ReflectionFailedException;
use Gember\EventSourcing\Util\File\Reflector\Reflector;
use Override;

final class ReflectorCommandHandlerRegistry implements CommandHandlerRegistry
{
    /**
     * @var array<class-string, array{class-string<EventSourcedUseCase>, CommandHandlerDefinition}>
     */
    private array $definitions = [];

    private bool $initialized = false;

    public function __construct(
        private readonly Finder $finder,
        private readonly Reflector $reflector,
        private readonly UseCaseResolver $useCaseResolver,
        private readonly string $path,
    ) {}

    /**
     * @throws CommandHandlerAlreadyRegisteredException
     * @throws CommandHandlerNotRegisteredException
     */
    #[Override]
    public function retrieve(string $commandName): array
    {
        $this->initialize();

        if (!array_key_exists($commandName, $this->definitions)) {
            throw CommandHandlerNotRegisteredException::withCommandName($commandName);
        }

        return $this->definitions[$commandName];
    }

    /**
     * @throws CommandHandlerAlreadyRegisteredException
     */
    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $definitions = [];

        foreach ($this->finder->getFiles($this->path) as $file) {
            if ($file === '') {
                continue;
            }

            try {
                $reflectionClass = $this->reflector->reflectClassFromFile($file);
            } catch (ReflectionFailedException) {
                // No class in this file (e.g. only a trait or functions)
                continue;
            }

            if (!$reflectionClass->implementsInterface(EventSourcedUseCase::class)) {
                continue;
            }

            /** @var class-string<EventSourcedUseCase> $useCaseClassName */
            $useCaseClassName = $reflectionClass->getName();

            $useCaseDefinition = $this->useCaseResolver->resolve($useCaseClassName);

            foreach ($useCaseDefinition->commandHandlers as $commandHandlerDefinition) {
                $commandClassName = $commandHandlerDefinition->commandClassName;

                if (array_key_exists($commandClassName, $definitions)) {
                    [$registeredUseCaseClassName, $registeredDefinition] = $definitions[$commandClassName];

                    throw CommandHandlerAlreadyRegisteredException::withCommandName(
                        $commandClassName,
                        sprintf('%s::%s', $registeredUseCaseClassName, $registeredDefinition->methodName),
                        sprintf('%s::%s', $useCaseClassName, $commandHandlerDefinition->methodName),
                    );
                }

                $definitions[$commandClassName] = [
                    $useCaseClassName,
                    $commandHandlerDefinition,
                ];
            }
        }

        $this->definitions = $definitions;
        $this->initialized = true;
    }
}

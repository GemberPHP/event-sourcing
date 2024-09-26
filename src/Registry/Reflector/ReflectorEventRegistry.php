<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\Reflector;

use Gember\EventSourcing\Registry\EventNotRegisteredException;
use Gember\EventSourcing\Registry\EventRegistry;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\NormalizedEventNameResolver;
use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\UnresolvableEventNameException;
use Gember\EventSourcing\Util\File\Finder\Finder;
use Gember\EventSourcing\Util\File\Reflector\ReflectionFailedException;
use Gember\EventSourcing\Util\File\Reflector\Reflector;
use Override;

final class ReflectorEventRegistry implements EventRegistry
{
    /**
     * @var array<string, class-string>
     */
    private array $events = [];

    public function __construct(
        private readonly Finder $finder,
        private readonly Reflector $reflector,
        private readonly NormalizedEventNameResolver $eventNameResolver,
        private readonly string $path,
    ) {}

    /**
     * @throws EventNotRegisteredException
     * @throws ReflectionFailedException
     * @throws UnresolvableEventNameException
     */
    #[Override]
    public function retrieve(string $eventName): string
    {
        $this->initialize();

        if (!array_key_exists($eventName, $this->events)) {
            throw EventNotRegisteredException::withEventName($eventName);
        }

        return $this->events[$eventName];
    }

    /**
     * @throws ReflectionFailedException
     * @throws UnresolvableEventNameException
     */
    private function initialize(): void
    {
        if ($this->events !== []) {
            return;
        }

        $files = $this->finder->getFiles($this->path);

        foreach ($files as $file) {
            if ($file === '') {
                continue;
            }

            $reflectionClass = $this->reflector->reflectClassFromFile($file);

            $eventName = $this->eventNameResolver->resolve($reflectionClass->getName());

            $this->events[$eventName] = $reflectionClass->getName();
        }
    }
}

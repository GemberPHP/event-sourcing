<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\Event\Reflector;

use Gember\EventSourcing\Registry\Event\EventAlreadyRegisteredException;
use Gember\EventSourcing\Registry\Event\EventNotRegisteredException;
use Gember\EventSourcing\Registry\Event\EventRegistry;
use Gember\EventSourcing\Resolver\DomainEvent\DomainEventResolver;
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

    private bool $initialized = false;

    public function __construct(
        private readonly Finder $finder,
        private readonly Reflector $reflector,
        private readonly DomainEventResolver $domainEventResolver,
        private readonly string $path,
    ) {}

    /**
     * @throws EventAlreadyRegisteredException
     * @throws EventNotRegisteredException
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
     * @throws EventAlreadyRegisteredException
     */
    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $events = [];

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

            $eventClassName = $reflectionClass->getName();
            $eventName = $this->domainEventResolver->resolve($eventClassName)->eventName;

            if (array_key_exists($eventName, $events) && $events[$eventName] !== $eventClassName) {
                throw EventAlreadyRegisteredException::withEventName($eventName, $events[$eventName], $eventClassName);
            }

            $events[$eventName] = $eventClassName;
        }

        $this->events = $events;
        $this->initialized = true;
    }
}

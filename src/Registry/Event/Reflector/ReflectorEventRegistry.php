<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\Event\Reflector;

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

    public function __construct(
        private readonly Finder $finder,
        private readonly Reflector $reflector,
        private readonly DomainEventResolver $domainEventResolver,
        private readonly string $path,
    ) {}

    /**
     * @throws EventNotRegisteredException
     * @throws ReflectionFailedException
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

            $eventName = $this->domainEventResolver->resolve($reflectionClass->getName())->eventName;

            $this->events[$eventName] = $reflectionClass->getName();
        }
    }
}

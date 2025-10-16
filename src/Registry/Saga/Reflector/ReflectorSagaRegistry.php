<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Registry\Saga\Reflector;

use Gember\EventSourcing\Registry\Saga\SagaRegistry;
use Gember\EventSourcing\Resolver\Saga\Default\EventSubscriber\SagaEventSubscriberResolver;
use Gember\EventSourcing\Resolver\Saga\SagaDefinition;
use Gember\EventSourcing\Resolver\Saga\SagaResolver;
use Gember\EventSourcing\Util\File\Finder\Finder;
use Gember\EventSourcing\Util\File\Reflector\Reflector;
use Override;

final class ReflectorSagaRegistry implements SagaRegistry
{
    /**
     * @var array<string, list<SagaDefinition>>
     */
    private array $definitions = [];

    public function __construct(
        private readonly Finder $finder,
        private readonly Reflector $reflector,
        private readonly SagaResolver $sagaResolver,
        private readonly SagaEventSubscriberResolver $sagaEventSubscriberResolver,
        private readonly string $path,
    ) {}

    #[Override]
    public function retrieve(string $sagaIdName): array
    {
        $this->initialize();

        if (!array_key_exists($sagaIdName, $this->definitions)) {
            return [];
        }

        return $this->definitions[$sagaIdName];
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

            // A saga must have subscribers
            $subscribers = $this->sagaEventSubscriberResolver->resolve($reflectionClass->getName());

            if ($subscribers === []) {
                continue;
            }

            $definition = $this->sagaResolver->resolve($reflectionClass->getName());

            foreach ($definition->sagaIds as $sagaIdDefinition) {
                $this->definitions[$sagaIdDefinition->sagaIdName][] = $definition;
            }
        }
    }
}

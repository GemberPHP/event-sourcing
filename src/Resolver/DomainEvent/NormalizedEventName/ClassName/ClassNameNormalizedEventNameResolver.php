<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\ClassName;

use Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName\NormalizedEventNameResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\FriendlyClassNamer;
use Override;

final readonly class ClassNameNormalizedEventNameResolver implements NormalizedEventNameResolver
{
    public function __construct(
        private FriendlyClassNamer $friendlyClassNamer,
    ) {}

    #[Override]
    public function resolve(string $eventClassName): string
    {
        return $this->friendlyClassNamer->createFriendlyClassName($eventClassName);
    }
}

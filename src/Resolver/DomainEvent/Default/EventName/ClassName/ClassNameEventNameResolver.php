<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\ClassName;

use Gember\EventSourcing\Resolver\DomainEvent\Default\EventName\EventNameResolver;
use Gember\EventSourcing\Util\String\FriendlyClassNamer\FriendlyClassNamer;
use Override;

/**
 * Fallback resolver, to generate an event name based on the FQCN.
 */
final readonly class ClassNameEventNameResolver implements EventNameResolver
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

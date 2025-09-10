<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainCommand\Default;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagResolver;
use Gember\EventSourcing\Resolver\DomainCommand\DomainCommandDefinition;
use Gember\EventSourcing\Resolver\DomainCommand\DomainCommandResolver;
use Override;

final readonly class DefaultDomainCommandResolver implements DomainCommandResolver
{
    public function __construct(
        private DomainTagResolver $domainTagResolver,
    ) {}

    #[Override]
    public function resolve(string $commandClassName): DomainCommandDefinition
    {
        $domainTags = $this->domainTagResolver->resolve($commandClassName);

        return new DomainCommandDefinition(
            $commandClassName,
            $domainTags,
        );
    }
}

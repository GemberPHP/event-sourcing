<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Common\DomainTag\Interface;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagDefinition;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagResolver;
use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagType;
use Gember\EventSourcing\Resolver\Common\DomainTag\UnresolvableDomainTagException;
use Gember\EventSourcing\UseCase\SpecifiedDomainTags;
use Override;

final readonly class InterfaceDomainTagResolver implements DomainTagResolver
{
    #[Override]
    public function resolve(string $className): array
    {
        if (!is_subclass_of($className, SpecifiedDomainTags::class)) {
            throw UnresolvableDomainTagException::create(
                $className,
                'Class does not implement SpecifiedDomainTags interface',
            );
        }

        return [
            new DomainTagDefinition('getDomainTags', DomainTagType::Method),
        ];
    }
}

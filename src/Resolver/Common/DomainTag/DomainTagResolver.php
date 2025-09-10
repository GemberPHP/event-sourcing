<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Common\DomainTag;

interface DomainTagResolver
{
    /**
     * @param class-string $className
     *
     * @throws UnresolvableDomainTagException
     *
     * @return list<DomainTagDefinition>
     */
    public function resolve(string $className): array;
}

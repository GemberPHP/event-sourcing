<?php

declare(strict_types=1);

namespace Gember\EventSourcing\DomainContext;

use Gember\EventSourcing\Resolver\DomainContext\SubscriberMethodForEvent\SubscriberMethodForEventResolver;
use Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties\DomainIdPropertiesResolver;
use Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties\UnresolvableDomainIdPropertiesException;

final class DomainContextAttributeRegistry
{
    private static DomainIdPropertiesResolver $domainIdsResolver;
    private static SubscriberMethodForEventResolver $subscriberMethodsResolver;

    public static function initialize(
        DomainIdPropertiesResolver $domainIdsResolver,
        SubscriberMethodForEventResolver $subscriberMethodsResolver,
    ): void {
        self::$domainIdsResolver = $domainIdsResolver;
        self::$subscriberMethodsResolver = $subscriberMethodsResolver;
    }

    /**
     * @param class-string<EventSourcedDomainContext> $domainContextClassName
     *
     * @throws UnresolvableDomainIdPropertiesException
     *
     * @return list<string>
     */
    public static function getDomainIdPropertiesForContext(string $domainContextClassName): array
    {
        return self::$domainIdsResolver->resolve($domainContextClassName);
    }

    /**
     * @param class-string<EventSourcedDomainContext> $domainContextClassName
     * @param class-string $eventClassName
     */
    public static function getContextSubscriberMethodForEvent(string $domainContextClassName, string $eventClassName): ?string
    {
        return self::$subscriberMethodsResolver->resolve($domainContextClassName, $eventClassName);
    }
}

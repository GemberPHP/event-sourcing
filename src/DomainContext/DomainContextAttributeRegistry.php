<?php

declare(strict_types=1);

namespace Gember\EventSourcing\DomainContext;

use Gember\EventSourcing\Resolver\DomainContext\SubscriberMethodForEvent\SubscriberMethodForEventResolver;
use Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties\DomainIdPropertiesResolver;
use Gember\EventSourcing\Resolver\DomainContext\DomainIdProperties\UnresolvableDomainIdPropertiesException;

/**
 * @template T of EventSourcedDomainContext
 */
final class DomainContextAttributeRegistry
{
    /**
     * @var DomainIdPropertiesResolver<T>
     */
    private static DomainIdPropertiesResolver $domainIdsResolver;

    /**
     * @var SubscriberMethodForEventResolver<T>
     */
    private static SubscriberMethodForEventResolver $subscriberMethodsResolver;

    /**
     * @param DomainIdPropertiesResolver<T> $domainIdsResolver
     * @param SubscriberMethodForEventResolver<T> $subscriberMethodsResolver
     */
    public static function initialize(
        DomainIdPropertiesResolver $domainIdsResolver,
        SubscriberMethodForEventResolver $subscriberMethodsResolver,
    ): void {
        self::$domainIdsResolver = $domainIdsResolver;
        self::$subscriberMethodsResolver = $subscriberMethodsResolver;
    }

    /**
     * @param class-string<EventSourcedDomainContext<T>> $domainContextClassName
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
     * @param class-string<EventSourcedDomainContext<T>> $domainContextClassName
     * @param class-string $eventClassName
     */
    public static function getContextSubscriberMethodForEvent(string $domainContextClassName, string $eventClassName): ?string
    {
        return self::$subscriberMethodsResolver->resolve($domainContextClassName, $eventClassName);
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase;

use Gember\EventSourcing\Resolver\UseCase\SubscriberMethodForEvent\SubscriberMethodForEventResolver;
use Gember\EventSourcing\Resolver\UseCase\DomainIdProperties\DomainIdPropertiesResolver;
use Gember\EventSourcing\Resolver\UseCase\DomainIdProperties\UnresolvableDomainIdPropertiesException;

final class UseCaseAttributeRegistry
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
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     *
     * @throws UnresolvableDomainIdPropertiesException
     *
     * @return list<string>
     */
    public static function getDomainIdPropertiesForUseCase(string $useCaseClassName): array
    {
        return self::$domainIdsResolver->resolve($useCaseClassName);
    }

    /**
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     * @param class-string $eventClassName
     */
    public static function getUseCaseSubscriberMethodForEvent(string $useCaseClassName, string $eventClassName): ?string
    {
        return self::$subscriberMethodsResolver->resolve($useCaseClassName, $eventClassName);
    }
}

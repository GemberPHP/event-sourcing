<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase;

use Gember\EventSourcing\Resolver\UseCase\SubscriberMethodForEvent\SubscriberMethodForEventResolver;
use Gember\EventSourcing\Resolver\UseCase\DomainTagProperties\DomainTagsPropertiesResolver;
use Gember\EventSourcing\Resolver\UseCase\DomainTagProperties\UnresolvableDomainTagPropertiesException;

final class UseCaseAttributeRegistry
{
    private static DomainTagsPropertiesResolver $domainTagsResolver;
    private static SubscriberMethodForEventResolver $subscriberMethodsResolver;

    public static function initialize(
        DomainTagsPropertiesResolver $domainTagsResolver,
        SubscriberMethodForEventResolver $subscriberMethodsResolver,
    ): void {
        self::$domainTagsResolver = $domainTagsResolver;
        self::$subscriberMethodsResolver = $subscriberMethodsResolver;
    }

    /**
     * @param class-string<EventSourcedUseCase> $useCaseClassName
     *
     * @throws UnresolvableDomainTagPropertiesException
     *
     * @return list<string>
     */
    public static function getDomainTagPropertiesForUseCase(string $useCaseClassName): array
    {
        return self::$domainTagsResolver->resolve($useCaseClassName);
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

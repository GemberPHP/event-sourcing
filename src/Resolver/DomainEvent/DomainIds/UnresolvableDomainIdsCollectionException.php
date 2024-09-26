<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\DomainIds;

final class UnresolvableDomainIdsCollectionException extends UnresolvableDomainIdsException
{
    /**
     * @var list<UnresolvableDomainIdsException>
     */
    private array $exceptions = [];

    /**
     * @param class-string $eventClassName
     */
    public static function withExceptions(
        string $eventClassName,
        string $message,
        UnresolvableDomainIdsException ...$exceptions,
    ): self {
        $exception = new self(sprintf('Unresolvable domainIds for event %s: %s', $eventClassName, $message));
        $exception->exceptions = array_values($exceptions);

        return $exception;
    }

    /**
     * @return list<UnresolvableDomainIdsException>
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }
}

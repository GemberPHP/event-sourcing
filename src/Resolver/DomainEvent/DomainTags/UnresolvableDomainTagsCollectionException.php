<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\DomainTags;

final class UnresolvableDomainTagsCollectionException extends UnresolvableDomainTagsException
{
    /**
     * @var list<UnresolvableDomainTagsException>
     */
    private array $exceptions = [];

    /**
     * @param class-string $eventClassName
     */
    public static function withExceptions(
        string $eventClassName,
        string $message,
        UnresolvableDomainTagsException ...$exceptions,
    ): self {
        $exception = new self(sprintf('Unresolvable domainTags for event %s: %s', $eventClassName, $message));
        $exception->exceptions = array_values($exceptions);

        return $exception;
    }

    /**
     * @return list<UnresolvableDomainTagsException>
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }
}

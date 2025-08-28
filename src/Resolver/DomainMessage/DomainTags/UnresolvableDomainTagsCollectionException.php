<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainMessage\DomainTags;

final class UnresolvableDomainTagsCollectionException extends UnresolvableDomainTagsException
{
    /**
     * @var list<UnresolvableDomainTagsException>
     */
    private array $exceptions = [];

    /**
     * @param class-string $messageClassName
     */
    public static function withExceptions(
        string $messageClassName,
        string $message,
        UnresolvableDomainTagsException ...$exceptions,
    ): self {
        $exception = new self(sprintf(
            'Unresolvable domainTags for domain message (event/command) %s: %s',
            $messageClassName,
            $message,
        ));

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

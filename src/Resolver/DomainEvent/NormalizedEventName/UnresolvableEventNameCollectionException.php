<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainEvent\NormalizedEventName;

final class UnresolvableEventNameCollectionException extends UnresolvableEventNameException
{
    /**
     * @var list<UnresolvableEventNameException>
     */
    private array $exceptions = [];

    /**
     * @param class-string $eventClassName
     */
    public static function withExceptions(
        string $eventClassName,
        string $message,
        UnresolvableEventNameException ...$exceptions,
    ): self {
        $exception = new self(sprintf('Unresolvable event name for class %s: %s', $eventClassName, $message));
        $exception->exceptions = array_values($exceptions);

        return $exception;
    }

    /**
     * @return list<UnresolvableEventNameException>
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }
}

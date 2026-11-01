<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository;

use Exception;

final class InvalidUseCaseDomainTagsException extends Exception
{
    /**
     * @param class-string $useCaseClassName
     */
    public static function noDomainTags(string $useCaseClassName): self
    {
        return new self(sprintf(
            'Use case %s has no domain tags: without them it has no consistency boundary',
            $useCaseClassName,
        ));
    }

    /**
     * @param class-string $useCaseClassName
     * @param list<string> $commandDomainTags
     * @param list<string> $useCaseDomainTags
     */
    public static function mismatch(string $useCaseClassName, array $commandDomainTags, array $useCaseDomainTags): self
    {
        return new self(sprintf(
            'Domain tags of use case %s [%s] do not match the domain tags of the command [%s]',
            $useCaseClassName,
            implode(', ', $useCaseDomainTags),
            implode(', ', $commandDomainTags),
        ));
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\DomainMessage\DomainTags\Interface;

use Gember\EventSourcing\UseCase\SpecifiedDomainTagsDomainMessage;
use Gember\EventSourcing\Resolver\DomainMessage\DomainTags\DomainTagsResolver;
use Gember\EventSourcing\Resolver\DomainMessage\DomainTags\UnresolvableDomainTagsException;
use Override;

final readonly class InterfaceDomainTagsResolver implements DomainTagsResolver
{
    #[Override]
    public function resolve(object $message): array
    {
        if (!is_subclass_of($message, SpecifiedDomainTagsDomainMessage::class)) {
            throw UnresolvableDomainTagsException::create(
                $message::class,
                'Domain message (event/command) does not implement SpecifiedDomainTagsDomainMessage interface',
            );
        }

        /** @var SpecifiedDomainTagsDomainMessage $message */
        return $message->getDomainTags();
    }
}

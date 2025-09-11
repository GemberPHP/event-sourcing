<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\Default;

use Gember\EventSourcing\Resolver\Common\DomainTag\DomainTagResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\CommandHandler\CommandHandlerResolver;
use Gember\EventSourcing\Resolver\UseCase\Default\EventSubscriber\EventSubscriberResolver;
use Gember\EventSourcing\Resolver\UseCase\UseCaseDefinition;
use Gember\EventSourcing\Resolver\UseCase\UseCaseResolver;
use Override;

final readonly class DefaultUseCaseResolver implements UseCaseResolver
{
    public function __construct(
        private DomainTagResolver $domainTagResolver,
        private CommandHandlerResolver $commandHandlerResolver,
        private EventSubscriberResolver $eventSubscriberResolver,
    ) {}

    #[Override]
    public function resolve(string $useCaseClassName): UseCaseDefinition
    {
        return new UseCaseDefinition(
            $useCaseClassName,
            $this->domainTagResolver->resolve($useCaseClassName),
            $this->commandHandlerResolver->resolve($useCaseClassName),
            $this->eventSubscriberResolver->resolve($useCaseClassName),
        );
    }
}

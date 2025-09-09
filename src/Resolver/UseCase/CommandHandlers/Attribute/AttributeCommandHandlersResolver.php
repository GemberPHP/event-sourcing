<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\CommandHandlers\Attribute;

use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\CommandHandlerDefinition;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\CommandHandlersResolver;
use Gember\EventSourcing\UseCase\Attribute\DomainCommandHandler;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use ReflectionMethod;
use ReflectionNamedType;

final readonly class AttributeCommandHandlersResolver implements CommandHandlersResolver
{
    public function __construct(
        private AttributeResolver $attributeResolver,
    ) {}

    #[Override]
    public function resolve(string $useCaseClassName): array
    {
        $methods = $this->attributeResolver->getMethodsWithAttribute(
            $useCaseClassName,
            DomainCommandHandler::class,
        );

        $definitions = [];

        /**
         * @var ReflectionMethod $reflectionMethod
         * @var DomainCommandHandler $attribute
         */
        foreach ($methods as [$reflectionMethod, $attribute]) {
            $parameters = $reflectionMethod->getParameters();

            if ($parameters === []) {
                continue;
            }

            $firstParameter = $parameters[array_key_first($parameters)];

            if (!$firstParameter->getType() instanceof ReflectionNamedType) {
                continue;
            }

            /** @var class-string $commandClassName */
            $commandClassName = $firstParameter->getType()->getName();

            $definitions[] = new CommandHandlerDefinition(
                $commandClassName,
                $useCaseClassName,
                $reflectionMethod->getName(),
                $attribute->policy,
            );
        }

        return $definitions;
    }
}

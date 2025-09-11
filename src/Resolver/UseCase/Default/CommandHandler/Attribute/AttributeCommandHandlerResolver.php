<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\Default\CommandHandler\Attribute;

use Gember\EventSourcing\Resolver\UseCase\CommandHandlerDefinition;
use Gember\EventSourcing\Resolver\UseCase\Default\CommandHandler\CommandHandlerResolver;
use Gember\EventSourcing\UseCase\Attribute\DomainCommandHandler;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use ReflectionMethod;
use ReflectionNamedType;

final readonly class AttributeCommandHandlerResolver implements CommandHandlerResolver
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

        $definition = [];

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

            $definition[] = new CommandHandlerDefinition(
                $commandClassName,
                $reflectionMethod->getName(),
                $attribute->policy,
            );
        }

        return $definition;
    }
}

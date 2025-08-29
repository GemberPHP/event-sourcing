<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\UseCase\CommandHandlers\Attribute;

use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\CommandHandlerDefinition;
use Gember\EventSourcing\Resolver\UseCase\CommandHandlers\CommandHandlersResolver;
use Gember\EventSourcing\UseCase\Attribute\DomainCommandHandler;
use Gember\EventSourcing\Util\Attribute\Resolver\AttributeResolver;
use Override;
use ReflectionMethod;

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

        foreach ($methods as $method) {
            if ($method->parameters === []) {
                continue;
            }

            $firstParameter = $method->parameters[array_key_first($method->parameters)];

            if ($firstParameter->type === null) {
                continue;
            }

            $attribute = $this->getAttributeForMethod($useCaseClassName, $method->name);

            $definitions[] = new CommandHandlerDefinition(
                $firstParameter->type,
                $useCaseClassName,
                $method->name,
                $attribute->policy,
            );
        }

        return $definitions;
    }

    private function getAttributeForMethod(string $useCaseClassName, string $methodName): DomainCommandHandler
    {
        $reflectionMethod = new ReflectionMethod($useCaseClassName, $methodName);
        $attributes = $reflectionMethod->getAttributes(DomainCommandHandler::class);

        /** @var DomainCommandHandler */
        return $attributes[0]->newInstance();
    }
}

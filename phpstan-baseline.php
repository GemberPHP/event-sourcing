<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$json of function json_decode expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Registry/Saga/Cached/CachedSagaRegistryDecorator.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot cast mixed to string\\.$#',
	'identifier' => 'cast.string',
	'count' => 2,
	'path' => __DIR__ . '/src/Resolver/Common/DomainTag/DomainTagValueHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$array of function array_map expects array, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Resolver/Common/DomainTag/DomainTagValueHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Resolver\\\\Common\\\\SagaId\\\\SagaIdValueHelper\\:\\:getSagaIdValue\\(\\) should return string\\|Stringable but returns mixed\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Resolver/Common/SagaId/SagaIdValueHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$sagaEventSubscriberResolver of class Gember\\\\EventSourcing\\\\Registry\\\\Saga\\\\Reflector\\\\ReflectorSagaRegistry constructor expects Gember\\\\EventSourcing\\\\Resolver\\\\Saga\\\\Default\\\\EventSubscriber\\\\SagaEventSubscriberResolver, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Registry/Saga/Cached/CachedSagaRegistryDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$sagaEventSubscriberResolver of class Gember\\\\EventSourcing\\\\Registry\\\\Saga\\\\Reflector\\\\ReflectorSagaRegistry constructor expects Gember\\\\EventSourcing\\\\Resolver\\\\Saga\\\\Default\\\\EventSubscriber\\\\SagaEventSubscriberResolver, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Registry/Saga/Reflector/ReflectorSagaRegistryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$serializer of class Gember\\\\EventSourcing\\\\Repository\\\\Rdbms\\\\RdbmsSagaStore constructor expects Gember\\\\DependencyContracts\\\\Util\\\\Serialization\\\\Serializer\\\\Serializer, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Repository/Rdbms/RdbmsSagaStoreTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$json of function json_decode expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Resolver/DomainCommand/Cached/CachedDomainCommandResolverDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$payload of static method Gember\\\\EventSourcing\\\\Resolver\\\\DomainCommand\\\\DomainCommandDefinition\\:\\:fromPayload\\(\\) expects array\\{commandClassName\\: class\\-string, domainTags\\: list\\<array\\{domainTagName\\: string, type\\: string\\}\\>\\}, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Resolver/DomainCommand/Cached/CachedDomainCommandResolverDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$json of function json_decode expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Resolver/DomainEvent/Cached/CachedDomainEventResolverDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$payload of static method Gember\\\\EventSourcing\\\\Resolver\\\\DomainEvent\\\\DomainEventDefinition\\:\\:fromPayload\\(\\) expects array\\{eventClassName\\: class\\-string, eventName\\: string, domainTags\\: list\\<array\\{domainTagName\\: string, type\\: string\\}\\>, sagaIds\\: list\\<array\\{sagaIdName\\: string, propertyName\\: string\\}\\>\\}, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Resolver/DomainEvent/Cached/CachedDomainEventResolverDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$json of function json_decode expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Resolver/Saga/Cached/CachedSagaResolverDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$payload of static method Gember\\\\EventSourcing\\\\Resolver\\\\Saga\\\\SagaDefinition\\:\\:fromPayload\\(\\) expects array\\{sagaClassName\\: class\\-string, sagaName\\: string, sagaId\\: array\\{sagaIdName\\: string, propertyName\\: string\\}, eventSubscribers\\: list\\<array\\{eventClassName\\: class\\-string, methodName\\: string, policy\\: string\\}\\>\\}, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Resolver/Saga/Cached/CachedSagaResolverDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$json of function json_decode expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Resolver/UseCase/Cached/CachedUseCaseResolverDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$payload of static method Gember\\\\EventSourcing\\\\Resolver\\\\UseCase\\\\UseCaseDefinition\\:\\:fromPayload\\(\\) expects array\\{useCaseClassName\\: class\\-string, domainTags\\: list\\<array\\{domainTagName\\: string, type\\: string\\}\\>, commandHandlers\\: list\\<array\\{commandClassName\\: class\\-string, methodName\\: string, policy\\: string\\}\\>, eventSubscribers\\: list\\<array\\{eventClassName\\: class\\-string, methodName\\: string\\}\\>\\}, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Resolver/UseCase/Cached/CachedUseCaseResolverDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$sagaEventSubscriberResolver of class Gember\\\\EventSourcing\\\\Registry\\\\Saga\\\\Reflector\\\\ReflectorSagaRegistry constructor expects Gember\\\\EventSourcing\\\\Resolver\\\\Saga\\\\Default\\\\EventSubscriber\\\\SagaEventSubscriberResolver, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Saga/SagaEventHandlerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$serializer of class Gember\\\\EventSourcing\\\\Repository\\\\Rdbms\\\\RdbmsSagaStore constructor expects Gember\\\\DependencyContracts\\\\Util\\\\Serialization\\\\Serializer\\\\Serializer, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Saga/SagaEventHandlerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\InvalidSaga\\\\TestInvalidSagaWithPrivateSagaId has an uninitialized readonly property \\$id\\. Assign it in the constructor\\.$#',
	'identifier' => 'property.uninitializedReadonly',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/InvalidSaga/TestInvalidSagaWithPrivateSagaId.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\InvalidSaga\\\\TestInvalidSagaWithPrivateSagaId\\:\\:\\$id is unused\\.$#',
	'identifier' => 'property.unused',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/InvalidSaga/TestInvalidSagaWithPrivateSagaId.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\Repository\\\\TestUseCaseRepository\\:\\:get\\(\\) should return T of Gember\\\\EventSourcing\\\\UseCase\\\\EventSourcedUseCase but returns Gember\\\\EventSourcing\\\\UseCase\\\\EventSourcedUseCase\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/Repository/TestUseCaseRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\Saga\\\\TestSaga\\:\\:\\$someId \\(string\\) does not accept string\\|null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/Saga/TestSaga.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\Saga\\\\TestSagaWithNamedInterface\\:\\:\\$someId is unused\\.$#',
	'identifier' => 'property.unused',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/Saga/TestSagaWithNamedInterface.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\Saga\\\\TestSagaWithoutName\\:\\:\\$someId is unused\\.$#',
	'identifier' => 'property.unused',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/Saga/TestSagaWithoutName.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\UseCase\\\\TestFullUseCase has an uninitialized readonly property \\$domainTag\\. Assign it in the constructor\\.$#',
	'identifier' => 'property.uninitializedReadonly',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/UseCase/TestFullUseCase.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\UseCase\\\\TestFullUseCase has an uninitialized readonly property \\$secondaryId\\. Assign it in the constructor\\.$#',
	'identifier' => 'property.uninitializedReadonly',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/UseCase/TestFullUseCase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\UseCase\\\\TestFullUseCase\\:\\:onTestUseCaseCreatedEvent\\(\\) is unused\\.$#',
	'identifier' => 'method.unused',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/UseCase/TestFullUseCase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\UseCase\\\\TestFullUseCase\\:\\:onTestUseCaseModifiedEvent\\(\\) is unused\\.$#',
	'identifier' => 'method.unused',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/UseCase/TestFullUseCase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\UseCase\\\\TestUseCase\\:\\:getDomainTags\\(\\) should return list\\<string\\|Stringable\\> but returns list\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/UseCase/TestUseCase.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\UseCase\\\\TestUseCaseWithCommandHandler\\:\\:getDomainTags\\(\\) should return list\\<string\\|Stringable\\> but returns list\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/UseCase/TestUseCaseWithCommandHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\UseCase\\\\TestUseCaseWithCommandHandler\\:\\:invalidWithParameterType\\(\\) has parameter \\$command with no type specified\\.$#',
	'identifier' => 'missingType.parameter',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/UseCase/TestUseCaseWithCommandHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$key of method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\Util\\\\Cache\\\\TestCache\\:\\:set\\(\\) expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/Util/Cache/TestCache.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

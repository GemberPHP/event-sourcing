<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$json of function json_decode expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Registry/Saga/Cached/CachedSagaRegistryDecorator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Repository\\\\Snapshot\\\\SnapshotUseCaseRepositoryDecorator\\:\\:get\\(\\) should return T of Gember\\\\EventSourcing\\\\UseCase\\\\EventSourcedUseCase but returns Gember\\\\EventSourcing\\\\UseCase\\\\EventSourcedUseCase\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Repository/Snapshot/SnapshotUseCaseRepositoryDecorator.php',
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
	'message' => '#^Method Gember\\\\EventSourcing\\\\Resolver\\\\Common\\\\SagaId\\\\SagaIdValueHelper\\:\\:getSagaIdValue\\(\\) should return string\\|Stringable\\|null but returns mixed\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Resolver/Common/SagaId/SagaIdValueHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$afterEventId on Gember\\\\EventSourcing\\\\EventStore\\\\StreamQuery\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/tests/Repository/Snapshot/SnapshotUseCaseRepositoryDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$domainTags on Gember\\\\EventSourcing\\\\Snapshot\\\\SnapshotEnvelope\\|null\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/tests/Repository/Snapshot/SnapshotUseCaseRepositoryDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\Repository\\\\Snapshot\\\\NativeTestSerializer\\:\\:deserialize\\(\\) should return object but returns mixed\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Repository/Snapshot/SnapshotUseCaseRepositoryDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\Repository\\\\Snapshot\\\\NoSnapshotTestUseCase\\:\\:getDomainTags\\(\\) should return list\\<string\\|Stringable\\> but returns list\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Repository/Snapshot/SnapshotUseCaseRepositoryDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\Repository\\\\Snapshot\\\\OnEventSnapshotTestUseCase\\:\\:getDomainTags\\(\\) should return list\\<string\\|Stringable\\> but returns list\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Repository/Snapshot/SnapshotUseCaseRepositoryDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\Repository\\\\Snapshot\\\\SnapshotTestUseCase\\:\\:getDomainTags\\(\\) should return list\\<string\\|Stringable\\> but returns list\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Repository/Snapshot/SnapshotUseCaseRepositoryDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\Repository\\\\Snapshot\\\\SourcingTimeSnapshotTestUseCase\\:\\:getDomainTags\\(\\) should return list\\<string\\|Stringable\\> but returns list\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Repository/Snapshot/SnapshotUseCaseRepositoryDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Gember\\\\EventSourcing\\\\Test\\\\Repository\\\\Snapshot\\\\NoSnapshotTestUseCase\\:\\:\\$id \\(string\\) does not accept string\\|null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Repository/Snapshot/SnapshotUseCaseRepositoryDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Gember\\\\EventSourcing\\\\Test\\\\Repository\\\\Snapshot\\\\OnEventSnapshotTestUseCase\\:\\:\\$id \\(string\\) does not accept string\\|null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Repository/Snapshot/SnapshotUseCaseRepositoryDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Gember\\\\EventSourcing\\\\Test\\\\Repository\\\\Snapshot\\\\SnapshotTestUseCase\\:\\:\\$id \\(string\\) does not accept string\\|null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Repository/Snapshot/SnapshotUseCaseRepositoryDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Gember\\\\EventSourcing\\\\Test\\\\Repository\\\\Snapshot\\\\SourcingTimeSnapshotTestUseCase\\:\\:\\$id \\(string\\) does not accept string\\|null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/tests/Repository/Snapshot/SnapshotUseCaseRepositoryDecoratorTest.php',
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
	'message' => '#^Parameter \\#1 \\$payload of static method Gember\\\\EventSourcing\\\\Resolver\\\\Saga\\\\SagaDefinition\\:\\:fromPayload\\(\\) expects array\\{sagaClassName\\: class\\-string, sagaName\\: string, sagaIds\\: list\\<array\\{sagaIdName\\: string, propertyName\\: string\\}\\>, eventSubscribers\\: list\\<array\\{eventClassName\\: class\\-string, methodName\\: string, policy\\: string\\}\\>\\}, mixed given\\.$#',
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
	'message' => '#^Parameter \\#1 \\$payload of static method Gember\\\\EventSourcing\\\\Resolver\\\\UseCase\\\\UseCaseDefinition\\:\\:fromPayload\\(\\) expects array\\{useCaseClassName\\: class\\-string, domainTags\\: list\\<array\\{domainTagName\\: string, type\\: string\\}\\>, commandHandlers\\: list\\<array\\{commandClassName\\: class\\-string, methodName\\: string, policy\\: string\\}\\>, eventSubscribers\\: list\\<array\\{eventClassName\\: class\\-string, methodName\\: string\\}\\>, snapshotDefinition\\: array\\{afterEvents\\: int\\|null, afterSourcingTimeMs\\: int\\|null, onEvents\\: list\\<class\\-string\\>\\|null\\}\\|null\\}, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Resolver/UseCase/Cached/CachedUseCaseResolverDecoratorTest.php',
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
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\UseCase\\\\TestFullUseCase\\:\\:getDomainTags\\(\\) should return list\\<string\\|Stringable\\> but returns list\\.$#',
	'identifier' => 'return.type',
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
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\UseCase\\\\TestUseCaseWithoutAppliedAt\\:\\:getDomainTags\\(\\) should return list\\<string\\|Stringable\\> but returns list\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/UseCase/TestUseCaseWithoutAppliedAt.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$key of method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\Util\\\\Cache\\\\TestCache\\:\\:set\\(\\) expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/Util/Cache/TestCache.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$onEvent of class Gember\\\\EventSourcing\\\\UseCase\\\\Attribute\\\\Snapshot constructor expects class\\-string\\|list\\<class\\-string\\>\\|null, \'EventA\' given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/UseCase/Attribute/SnapshotTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$onEvent of class Gember\\\\EventSourcing\\\\UseCase\\\\Attribute\\\\Snapshot constructor expects class\\-string\\|list\\<class\\-string\\>\\|null, \'SomeEvent\' given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/UseCase/Attribute/SnapshotTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$onEvents of class Gember\\\\EventSourcing\\\\UseCase\\\\Attribute\\\\Snapshot constructor expects list\\<class\\-string\\>\\|null, array\\{\'EventA\', \'EventB\'\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/UseCase/Attribute/SnapshotTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$onEvents of class Gember\\\\EventSourcing\\\\UseCase\\\\Attribute\\\\Snapshot constructor expects list\\<class\\-string\\>\\|null, array\\{\'EventB\', \'EventC\'\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/UseCase/Attribute/SnapshotTest.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

<?php declare(strict_types = 1);

$ignoreErrors = [];
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
	'message' => '#^Parameter \\#1 \\$payload of static method Gember\\\\EventSourcing\\\\Resolver\\\\DomainEvent\\\\DomainEventDefinition\\:\\:fromPayload\\(\\) expects array\\{eventClassName\\: class\\-string, eventName\\: string, domainTags\\: list\\<array\\{domainTagName\\: string, type\\: string\\}\\>\\}, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/Resolver/DomainEvent/Cached/CachedDomainEventResolverDecoratorTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\Repository\\\\TestUseCaseRepository\\:\\:get\\(\\) should return T of Gember\\\\EventSourcing\\\\UseCase\\\\EventSourcedUseCase but returns Gember\\\\EventSourcing\\\\UseCase\\\\EventSourcedUseCase\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/Repository/TestUseCaseRepository.php',
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

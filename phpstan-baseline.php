<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$json of function json_decode expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Util/Attribute/Resolver/Cached/CachedAttributeResolverDecorator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\UseCase\\\\TestUseCase\\:\\:getDomainTags\\(\\) should return list\\<string\\|Stringable\\> but returns list\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/UseCase/TestUseCase.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$key of method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\Util\\\\Cache\\\\TestCache\\:\\:set\\(\\) expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/Util/Cache/TestCache.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

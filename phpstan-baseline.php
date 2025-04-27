<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Method Gember\\\\EventSourcing\\\\Test\\\\TestDoubles\\\\DomainContext\\\\TestDomainContext\\:\\:getDomainIds\\(\\) should return list\\<string\\|Stringable\\> but returns list\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/TestDoubles/DomainContext/TestDomainContext.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

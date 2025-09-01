<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Util\File\Reflector\Native;

use Gember\EventSourcing\Util\File\Reflector\ReflectionFailedException;
use Gember\EventSourcing\Util\File\Reflector\Reflector;
use PhpToken;
use ReflectionClass;
use Override;

final readonly class NativeReflector implements Reflector
{
    #[Override]
    public function reflectClassFromFile(string $file): ReflectionClass
    {
        $content = file_get_contents($file);

        if (!$content) {
            throw ReflectionFailedException::classNotFound($file);
        }

        $tokens = PhpToken::tokenize($content);

        $namespace = '';
        $collectNamespace = false;

        foreach ($tokens as $index => $token) {
            if ($token->is(T_NAMESPACE)) {
                // Start collecting namespace parts
                $namespace = '';
                $collectNamespace = true;

                continue;
            }

            if ($collectNamespace) {
                if ($token->is(T_NAME_QUALIFIED)) {
                    $namespace .= $token->text;
                } elseif ($token->is(T_WHITESPACE)) {
                    continue;
                } else {
                    // End of namespace declaration
                    $collectNamespace = false;
                }
            }

            if ($token->is([T_CLASS, T_INTERFACE, T_ENUM])) {
                // Skip anonymous classes
                $next = $tokens[$index + 1] ?? null;
                while ($next && $next->is(T_WHITESPACE)) {
                    ++$index;
                    $next = $tokens[$index + 1] ?? null;
                }

                if ($next && $next->is(T_STRING)) {
                    /** @var class-string $className */
                    $className = trim($namespace . '\\' . $next->text, '\\');

                    return new ReflectionClass($className);
                }
            }
        }

        throw ReflectionFailedException::classNotFound($file);
    }
}

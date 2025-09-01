<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Util\File\Reflector\Native;

use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCase;
use Gember\EventSourcing\Util\File\Reflector\Native\NativeReflector;
use Gember\EventSourcing\Util\File\Reflector\ReflectionFailedException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Override;

/**
 * @internal
 */
final class NativeReflectorTest extends TestCase
{
    private NativeReflector $reflector;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->reflector = new NativeReflector();
    }

    #[Test]
    public function itShouldReflectFileName(): void
    {
        $reflectionClass = $this->reflector->reflectClassFromFile(__DIR__ . '/../../../../TestDoubles/UseCase/TestUseCase.php');

        self::assertSame(TestUseCase::class, $reflectionClass->getName());
    }

    #[Test]
    public function itShouldThrowExceptionWhenClassNotFound(): void
    {
        self::expectException(ReflectionFailedException::class);

        $this->reflector->reflectClassFromFile(__DIR__ . '/../../../../TestDoubles/Util/File/Reflector/no-class.php');
    }
}

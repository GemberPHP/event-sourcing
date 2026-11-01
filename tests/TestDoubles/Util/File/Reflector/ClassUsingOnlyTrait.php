<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\File\Reflector;

/**
 * Uses OnlyTrait, so the file with only that trait is a realistic (and analysed) trait.
 */
final class ClassUsingOnlyTrait
{
    use OnlyTrait;
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase\Attribute;

enum CreationPolicy: string
{
    case Never = 'never';
    case IfMissing = 'if_missing';
}

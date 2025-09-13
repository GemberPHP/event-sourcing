<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Common;

enum CreationPolicy: string
{
    case Never = 'never';
    case IfMissing = 'if_missing';
}

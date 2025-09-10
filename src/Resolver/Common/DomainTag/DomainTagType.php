<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Resolver\Common\DomainTag;

enum DomainTagType: string
{
    case Property = 'property';
    case Method = 'method';
}

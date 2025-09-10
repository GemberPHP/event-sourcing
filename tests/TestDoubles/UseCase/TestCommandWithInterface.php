<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\UseCase;

use Gember\EventSourcing\UseCase\SpecifiedDomainTags;

final readonly class TestCommandWithInterface implements SpecifiedDomainTags
{
    public function __construct(
        public string $domainTag,
    ) {}

    public function getDomainTags(): array
    {
        return [
            'f400bf1d-ad50-4e46-abcf-0bff36cc8df5',
        ];
    }
}

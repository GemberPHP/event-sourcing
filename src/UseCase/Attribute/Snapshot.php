<?php

declare(strict_types=1);

namespace Gember\EventSourcing\UseCase\Attribute;

use Attribute;
use Gember\EventSourcing\Util\Time\Duration;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Snapshot
{
    /**
     * @var list<class-string>|null
     */
    public ?array $onEvents;

    /**
     * @param class-string|list<class-string>|null $onEvent
     * @param list<class-string>|null $onEvents
     */
    public function __construct(
        public ?int $afterEvents = null,
        public ?Duration $afterSourcingTime = null,
        string|array|null $onEvent = null,
        ?array $onEvents = null,
    ) {
        if ($afterEvents !== null && $afterEvents < 1) {
            throw new \InvalidArgumentException('afterEvents must be at least 1');
        }

        $this->onEvents = match (true) {
            $onEvent !== null && $onEvents !== null => [...(array) $onEvent, ...$onEvents],
            $onEvent !== null => (array) $onEvent,
            $onEvents !== null => $onEvents,
            default => null,
        };
    }
}

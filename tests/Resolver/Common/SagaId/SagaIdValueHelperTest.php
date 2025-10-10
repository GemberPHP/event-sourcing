<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Resolver\Common\SagaId;

use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdDefinition;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdValueHelper;
use Gember\EventSourcing\Test\TestDoubles\UseCase\TestUseCaseCreatedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SagaIdValueHelperTest extends TestCase
{
    #[Test]
    public function itShouldGetIdValueFromObject(): void
    {
        $event = new TestUseCaseCreatedEvent(
            '01K76K7H4V25CD1VQJQP2TG6Y6',
            'some-id',
        );

        self::assertSame(
            '01K76K7H4V25CD1VQJQP2TG6Y6',
            SagaIdValueHelper::getSagaIdValue($event, new SagaIdDefinition('id', 'id')),
        );
    }
}

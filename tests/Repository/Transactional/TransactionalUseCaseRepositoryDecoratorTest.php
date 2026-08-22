<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\Repository\Transactional;

use Gember\EventSourcing\Repository\Transactional\TransactionalUseCaseRepositoryDecorator;
use Gember\EventSourcing\Repository\UseCaseNotFoundException;
use Gember\EventSourcing\Repository\UseCaseRepository;
use Gember\EventSourcing\Test\TestDoubles\Util\Transaction\TestTransactional;
use Gember\EventSourcing\UseCase\DomainEventEnvelope;
use Gember\EventSourcing\UseCase\EventSourcedUseCase;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stringable;

/**
 * @internal
 */
final class TransactionalUseCaseRepositoryDecoratorTest extends TestCase
{
    private InMemoryUseCaseRepository $innerRepository;
    private TestTransactional $transactional;
    private TransactionalUseCaseRepositoryDecorator $decorator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerRepository = new InMemoryUseCaseRepository();
        $this->transactional = new TestTransactional();
        $this->decorator = new TransactionalUseCaseRepositoryDecorator(
            $this->innerRepository,
            $this->transactional,
        );
    }

    #[Test]
    public function itShouldDelegateGet(): void
    {
        $this->innerRepository->getResult = new StubUseCase();

        $result = $this->decorator->get(StubUseCase::class, 'tag-1');

        self::assertSame($this->innerRepository->getResult, $result);
        self::assertTrue($this->innerRepository->getWasCalled);
    }

    #[Test]
    public function itShouldDelegateHas(): void
    {
        $this->innerRepository->hasResult = true;

        self::assertTrue($this->decorator->has(StubUseCase::class, 'tag-1'));
        self::assertTrue($this->innerRepository->hasWasCalled);
    }

    #[Test]
    public function itShouldWrapSaveInTransaction(): void
    {
        $useCase = new StubUseCase();

        $this->decorator->save($useCase);

        self::assertTrue($this->transactional->wasCalled);
        self::assertTrue($this->innerRepository->saveWasCalled);
    }

    #[Test]
    public function itShouldNotWrapGetInTransaction(): void
    {
        $this->innerRepository->getResult = new StubUseCase();

        $this->decorator->get(StubUseCase::class, 'tag-1');

        self::assertFalse($this->transactional->wasCalled);
    }
}

/**
 * @internal
 */
final class StubUseCase implements EventSourcedUseCase
{
    public function getDomainTags(): array
    {
        return [];
    }

    public function getLastEventId(): ?string
    {
        return null;
    }

    public function getAppliedEvents(): array
    {
        return [];
    }

    public function clearAppliedEvents(): void {}

    public function setLastEventId(string $lastEventId): void {}

    public static function reconstitute(DomainEventEnvelope ...$envelopes): self
    {
        return new self();
    }

    public static function reconstituteFromSnapshot(object $snapshotState, DomainEventEnvelope ...$envelopes): self
    {
        return new self();
    }
}

/**
 * @internal
 */
final class InMemoryUseCaseRepository implements UseCaseRepository
{
    public bool $getWasCalled = false;
    public bool $hasWasCalled = false;
    public bool $saveWasCalled = false;
    public ?EventSourcedUseCase $getResult = null;
    public bool $hasResult = false;

    public function get(string $useCaseClassName, string|Stringable ...$domainTag): EventSourcedUseCase
    {
        $this->getWasCalled = true;

        return $this->getResult ?? throw UseCaseNotFoundException::create(); // @phpstan-ignore return.type
    }

    public function has(string $useCaseClassName, string|Stringable ...$domainTag): bool
    {
        $this->hasWasCalled = true;

        return $this->hasResult;
    }

    public function save(EventSourcedUseCase $useCase): void
    {
        $this->saveWasCalled = true;
    }
}

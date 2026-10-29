<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository\Rdbms;

use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaConcurrencyException;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaStoreRepository;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaNotFoundException;
use Gember\DependencyContracts\Util\Serialization\Serializer\Serializer;
use Gember\EventSourcing\Repository\SagaConcurrencyException;
use Gember\EventSourcing\Repository\SagaNotFoundException;
use Gember\EventSourcing\Repository\SagaStore;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdValueHelper;
use Gember\EventSourcing\Resolver\Saga\SagaResolver;
use Gember\EventSourcing\Util\Time\Clock\Clock;
use Stringable;
use Override;
use WeakMap;

final readonly class RdbmsSagaStore implements SagaStore
{
    /**
     * Revision each loaded or saved saga is at, used as optimistic lock on its next save.
     *
     * @var WeakMap<object, int>
     */
    private WeakMap $revisions;

    public function __construct(
        private SagaResolver $sagaResolver,
        private RdbmsSagaStoreRepository $sagaStoreRepository,
        private SagaFactory $sagaFactory,
        private Serializer $serializer,
        private Clock $clock,
    ) {
        $this->revisions = new WeakMap();
    }

    #[Override]
    public function get(string $sagaClassName, Stringable|string $sagaId): object
    {
        $sagaDefinition = $this->sagaResolver->resolve($sagaClassName);

        try {
            $rdbmsSaga = $this->sagaStoreRepository->get($sagaDefinition->sagaName, $sagaId);
        } catch (RdbmsSagaNotFoundException) {
            throw SagaNotFoundException::create();
        }

        $saga = $this->sagaFactory->createFromRdbmsSaga($sagaClassName, $rdbmsSaga);

        $this->revisions[$saga] = $rdbmsSaga->revision;

        return $saga;
    }

    #[Override]
    public function save(object $saga): void
    {
        $sagaDefinition = $this->sagaResolver->resolve($saga::class);

        try {
            $rdbmsSaga = $this->sagaStoreRepository->save(
                $sagaDefinition->sagaName,
                $this->serializer->serialize($saga),
                $this->clock->now(),
                $this->revisions[$saga] ?? null,
                ...array_filter(
                    array_map(
                        fn($sagaIdDefinition) => SagaIdValueHelper::getSagaIdValue($saga, $sagaIdDefinition),
                        $sagaDefinition->sagaIds,
                    ),
                    fn($sagaId) => $sagaId !== null,
                ),
            );
        } catch (RdbmsSagaConcurrencyException $exception) {
            throw SagaConcurrencyException::create($exception);
        }

        $this->revisions[$saga] = $rdbmsSaga->revision;
    }
}

<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Repository\Rdbms;

use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaStoreRepository;
use Gember\DependencyContracts\EventStore\Saga\RdbmsSagaNotFoundException;
use Gember\DependencyContracts\Util\Serialization\Serializer\Serializer;
use Gember\EventSourcing\Repository\SagaNotFoundException;
use Gember\EventSourcing\Repository\SagaStore;
use Gember\EventSourcing\Resolver\Common\SagaId\SagaIdValueHelper;
use Gember\EventSourcing\Resolver\Saga\SagaResolver;
use Gember\EventSourcing\Util\Time\Clock\Clock;
use Stringable;
use Override;

final readonly class RdbmsSagaStore implements SagaStore
{
    public function __construct(
        private SagaResolver $sagaResolver,
        private RdbmsSagaStoreRepository $sagaStoreRepository,
        private SagaFactory $sagaFactory,
        private Serializer $serializer,
        private Clock $clock,
    ) {}

    #[Override]
    public function get(string $sagaClassName, Stringable|string $sagaId): object
    {
        $sagaDefinition = $this->sagaResolver->resolve($sagaClassName);

        try {
            $rdbmsSaga = $this->sagaStoreRepository->get($sagaDefinition->sagaName, $sagaId);
        } catch (RdbmsSagaNotFoundException) {
            throw SagaNotFoundException::create();
        }

        return $this->sagaFactory->createFromRdbmsSaga($sagaClassName, $rdbmsSaga);
    }

    #[Override]
    public function save(object $saga): void
    {
        $sagaDefinition = $this->sagaResolver->resolve($saga::class);

        $this->sagaStoreRepository->save(
            $sagaDefinition->sagaName,
            $this->serializer->serialize($saga),
            $this->clock->now(),
            ...array_filter(
                array_map(
                    fn($sagaIdDefinition) => SagaIdValueHelper::getSagaIdValue($saga, $sagaIdDefinition),
                    $sagaDefinition->sagaIds,
                ),
                fn($sagaId) => $sagaId !== null,
            ),
        );
    }
}

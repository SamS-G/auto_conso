<?php

namespace App\Repositories;

use App\Database\QueryBuilder;
use App\Entities\ConsumptionData;
use App\Repositories\Interfaces\RepositoryInterface;
use App\Services\FileLoggerService;
use App\Services\PdoService;
use PDO;

class ConsumptionDataRepository extends AbstractRepository implements RepositoryInterface
{
    public function __construct(
        PdoService $pdoService,
        QueryBuilder $queryBuilder,
        FileLoggerService $fileLoggerService
    ) {
        parent::__construct($pdoService, $queryBuilder, $fileLoggerService);
    }

    protected static function getEntityClassName(): string
    {
        return ConsumptionData::class;
    }
    protected static function getTableName(): string
    {
        return ConsumptionData::$table;
    }
    protected function getEntityData(object $entity): array
    {
        if (!$entity instanceof ConsumptionData) {
            throw new \InvalidArgumentException("L'entité doit être une instance de " . ConsumptionData::class);
        }
        return [
            'model_id' => $entity->getModelId(),
            'city_consumption' => $entity->getCityConsumption(),
            'extra_city_consumption' => $entity->getExtraCityConsumption(),
            'mixed_consumption' => $entity->getMixedConsumption(),
        ];
    }
}

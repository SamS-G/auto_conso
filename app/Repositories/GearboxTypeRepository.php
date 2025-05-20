<?php

namespace App\Repositories;

use App\Database\QueryBuilder;
use App\Entities\GearboxType;
use App\Repositories\Interfaces\RepositoryInterface;
use App\Services\FileLoggerService;
use App\Services\PdoService;

class GearboxTypeRepository extends AbstractRepository implements RepositoryInterface
{
    public function __construct(
        PdoService $pdoService,
        QueryBuilder $queryBuilder,
        FileLoggerService $fileLoggerService
    ) {
        parent::__construct($pdoService, $queryBuilder, $fileLoggerService);
    }
    protected function getEntityData(object $entity): array
    {
        if (!$entity instanceof GearboxType) {
            throw new \InvalidArgumentException("L'entité doit être une instance de " . GearboxType::class);
        }
        return [
            'id' => $entity->getId(),
           'transmission' => $entity->getTransmission()
        ];
    }
    protected static function getTableName(): string
    {
        return GearboxType::$table;
    }

    protected static function getEntityClassName(): string
    {
        return GearboxType::class;
    }
}

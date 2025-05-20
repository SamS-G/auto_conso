<?php

namespace App\Repositories;

use App\Database\QueryBuilder;
use App\Entities\Brand;
use App\Repositories\Interfaces\RepositoryInterface;
use App\Services\FileLoggerService;
use App\Services\PdoService;

class BrandRepository extends AbstractRepository implements RepositoryInterface
{
    public function __construct(
        PdoService $pdoService,
        QueryBuilder $queryBuilder,
        FileLoggerService $fileLoggerService
    ) {
        parent::__construct($pdoService, $queryBuilder, $fileLoggerService);
    }

    public static function getEntityClassName(): string
    {
        return Brand::class;
    }

    protected static function getTableName(): string
    {
        return Brand::$table;
    }
    protected function getEntityData(object $entity): array
    {
        if (!$entity instanceof Brand) {
            throw new \InvalidArgumentException("L'entité doit être une instance de " . Brand::class);
        }
        return [
            'name' => $entity->getBrandName(),
            'nationality' => $entity->getNationality(),
        ];
    }
}

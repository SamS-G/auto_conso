<?php

namespace App\Repositories\Interfaces;

interface CarModelRepositoryInterface extends RepositoryInterface
{
    public function findCarDetailsById(int $id): array;
    public function findAllPaginated(
        array $columns = ['*'],
        string $orderBy = null,
        string $orderDirection = 'ASC',
        array $joins = [],
        int $offset = null,
        int $limit = null
    ): array;
    public function searchVehicles(array $filters, int $limit, int $offset): array;
    public function countFilteredCarsItems(string $tableName, array $filters): int;
}

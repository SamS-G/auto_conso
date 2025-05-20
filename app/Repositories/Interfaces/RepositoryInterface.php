<?php

namespace App\Repositories\Interfaces;

interface RepositoryInterface
{
    public function findById(int $id): ?array;
    public function findOneBy(array $criteria): ?object;
    public function findEntities(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): array;
    public function findAllPaginated(
        array $columns = ['*'],
        string $orderBy = null,
        string $orderDirection = 'ASC',
        array $joins = [],
        int $offset = null,
        int $limit = null
    ): array;
    public function delete(int $id): bool;
}

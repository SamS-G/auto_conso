<?php

namespace App\Services;

use App\Exceptions\DataBaseException;
use App\Exceptions\QueryBuilderException;
use App\Repositories\AbstractRepository;
use App\Repositories\Interfaces\RepositoryInterface;

class AbstractService
{
    private RepositoryInterface $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAllPaginated(
        array $columns = ['*'],
        string $orderBy = null,
        string $orderDirection = 'ASC',
        array $joins = [],
        int $offset = null,
        int $limit = null
    ): ?array {
        return $this->repository->findAllPaginated(
            $columns,
            $orderBy,
            $orderDirection,
            $joins,
            $offset,
            $limit
        );
    }

    /**
     * @throws DataBaseException
     */
    public function getEnumValues(array $sources, $byTable = true): array
    {
        return $byTable
            ? $this->repository->findEnumFromTables($sources)
            : $this->repository->findEnumFromColumns($sources);
    }

    /**
     * @throws DataBaseException
     */
    public function getTotalItems(string $table): int
    {
        return $this->repository->findTotalTableItems($table);
    }
}

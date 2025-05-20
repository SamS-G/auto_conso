<?php

namespace Tests\Unit\app\Repositories;

use App\Database\QueryBuilder;
use App\Exceptions\DataBaseException;
use App\Exceptions\QueryBuilderException;
use App\Repositories\CarModelRepository;
use App\Services\FileLoggerService;
use App\Services\PdoService;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class CarModelRepositoryTest extends TestCase
{
    private $pdo;
    private $stmt;
    private $queryBuilder;
    private CarModelRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->stmt = $this->createMock(PDOStatement::class);

        $pdoService = $this->createMock(PdoService::class);
        $pdoService->method('getConnection')->willReturn($this->pdo);

        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $logger = $this->createMock(FileLoggerService::class);

        $this->repository = new CarModelRepository(
            $pdoService,
            $this->queryBuilder,
            $logger
        );
    }

    public function testSearchVehiclesReturnsArray()
    {
        $filters = ['brand_id' => 1];

        $this->queryBuilder->method('prepareColumns')->willReturn(['car_model.id', 'car_model.model_name']);
        $this->queryBuilder->method('buildWhereClause')->willReturn("WHERE car_model.brand_id = :brand_id");
        $this->queryBuilder->method('buildJoinClauses')->willReturn('LEFT JOIN brand ON car_model.brand_id = brand.id');
        $this->queryBuilder->method('buildPaginatedQuery')->willReturn('SELECT * FROM car_model');

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);

        $this->stmt->method('bindValue');
        $this->stmt->expects($this->once())->method('execute');
        $this->stmt->method('fetchAll')->willReturn([['id' => 1, 'model_name' => 'Test']]);

        $result = $this->repository->searchVehicles($filters);
        $this->assertIsArray($result);
    }

    public function testFindCarDetailsByIdReturnsEntityArray()
    {
        $id = 1;

        $this->queryBuilder->method('prepareColumns')->willReturn(['car_model.model_name']);
        $this->queryBuilder->method('buildWhereClause')->willReturn("WHERE car_model.id = :id");
        $this->queryBuilder->method('buildJoinClauses')->willReturn('');
        $this->queryBuilder->method('buildOrderByClause')->willReturn('');
        $this->queryBuilder->method('buildQuery')->willReturn("SELECT * FROM car_model WHERE id = :id");

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())->method('bindValue');
        $this->stmt->expects($this->once())->method('execute');
        $this->stmt->method('fetchAll')->willReturn([
            ['model_name' => 'Test', 'energy_type' => 'ES']
        ]);

        $result = $this->repository->findCarDetailsById($id);
        $this->assertIsArray($result);
    }

    public function testCountFilteredCarsItemsReturnsInt()
    {
        $filters = ['brand_id' => 1];

        $this->queryBuilder->method('buildJoinClauses')->willReturn('');
        $this->queryBuilder->method('buildWhereClause')->willReturn('WHERE brand_id = :brand_id');

        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('bindValue');
        $this->stmt->expects($this->once())->method('execute');
        $this->stmt->method('fetchColumn')->willReturn(5);

        $count = $this->repository->countFilteredCarsItems('car_model', $filters);
        $this->assertSame(5, $count);
    }

    public function testSearchVehiclesThrowsDatabaseExceptionOnPdoError()
    {
        $filters = ['brand_id' => 1];

        $this->queryBuilder->method('prepareColumns')->willReturn(['car_model.id']);
        $this->queryBuilder->method('buildWhereClause')->willReturn("WHERE car_model.brand_id = :brand_id");
        $this->queryBuilder->method('buildJoinClauses')->willReturn('');
        $this->queryBuilder->method('buildPaginatedQuery')->willReturn("SELECT * FROM car_model");

        $this->pdo->method('prepare')->willThrowException(new PDOException("Database error"));

        $this->expectException(DataBaseException::class);
        $this->repository->searchVehicles($filters);
    }

    public function testFindCarDetailsByIdThrowsDatabaseExceptionOnQueryBuilderError()
    {
        $fileLoggerEntity = $this->createMock(FileLoggerService::class);

        $this->queryBuilder->method('prepareColumns')
            ->willThrowException(new QueryBuilderException($fileLoggerEntity, "Invalid column"));

        $this->expectException(DataBaseException::class);
        $this->repository->findCarDetailsById(1);
    }
}

<?php

namespace Tests\Unit\app\Services;

use App\DataTransferObjects\CarDetailsDTO;
use App\Entities\CarModel;
use App\Exceptions\DataBaseException;
use App\Exceptions\QueryBuilderException;
use App\Exceptions\ValidationException;
use App\Repositories\CarModelRepository;
use App\Services\CarModelService;
use App\Services\DTOCarFactoryService;
use App\Services\FileLoggerService;
use App\Validation\CarModelSearchValidator;
use PHPUnit\Framework\TestCase;

class CarModelServiceTest extends TestCase
{
    private $carRepository;
    private $validatorService;
    private $dtoFactory;
    private CarModelService $carModelService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carRepository = $this->createMock(CarModelRepository::class);
        $this->validatorService = $this->createMock(CarModelSearchValidator::class);
        $this->dtoFactory = $this->createMock(DTOCarFactoryService::class);

        $this->carModelService = new CarModelService(
            $this->carRepository,
            $this->validatorService,
            $this->dtoFactory
        );
    }

    /**
     * @test
     */
    public function getCarDetailsByIdShouldReturnDtoWhenCarExists()
    {
        // Arrange
        $carId = 123;
        $carModelEntity = $this->createMock(CarModel::class);
        $carData = [$carModelEntity]; // Tableau d'objets CarModel
        $expectedDto = $this->createMock(CarDetailsDTO::class);

        $this->carRepository->expects($this->once())
            ->method('findCarDetailsById')
            ->with($carId)
            ->willReturn($carData);

        $this->dtoFactory->expects($this->once())
            ->method('createCarDetailsDTO')
            ->with($carModelEntity) // Passage de l'entité et non du tableau
            ->willReturn($expectedDto);

        // Act
        $result = $this->carModelService->getCarDetailsById($carId);

        // Assert
        $this->assertSame($expectedDto, $result);
    }

    /**
     * @test
     */
    public function getCarDetailsByIdShouldReturnEmptyArrayWhenCarDoesNotExist()
    {
        // Arrange
        $carId = 999;
        $emptyResult = [];

        $this->carRepository->expects($this->once())
            ->method('findCarDetailsById')
            ->with($carId)
            ->willReturn($emptyResult);

        $this->dtoFactory->expects($this->never())
            ->method('createCarDetailsDTO');

        // Act
        $result = $this->carModelService->getCarDetailsById($carId);

        // Assert
        $this->assertSame($emptyResult, $result);
    }

    /**
     * @test
     */
    public function getAllCarsModelsShouldReturnDtoList()
    {
        // Arrange
        $columns = ['id', 'model'];
        $orderBy = 'id';
        $orderDirection = 'ASC';
        $joins = [];
        $offset = 0;
        $limit = 10;

        $carModel1 = $this->createMock(CarModel::class);
        $carModel2 = $this->createMock(CarModel::class);

        $carsData = [$carModel1, $carModel2]; // Tableau d'objets CarModel

        $expectedDtoList = [
            $this->createMock(CarDetailsDTO::class),
            $this->createMock(CarDetailsDTO::class)
        ];

        $this->carRepository->expects($this->once())
            ->method('findAllPaginated')
            ->with($columns, $orderBy, $orderDirection, $joins, $offset, $limit)
            ->willReturn($carsData);

        $this->dtoFactory->expects($this->once())
            ->method('createCarListDTO')
            ->with($carsData)
            ->willReturn($expectedDtoList);

        // Act
        $result = $this->carModelService->getAllCarsModels($columns, $orderBy, $orderDirection, $joins, $offset, $limit);

        // Assert
        $this->assertSame($expectedDtoList, $result);
    }

    /**
     * @test
     */
    public function validateSearchFormDataShouldCallValidator()
    {
        // Arrange
        $data = ['brand' => 'Toyota', 'year' => 2020];

        $this->validatorService->expects($this->once())
            ->method('validateForSearch')
            ->with($data);

        // Act & Assert (no exception expected)
        $this->carModelService->validateSearchFormData($data);
    }

    /**
     * @test
     */
    public function validateSearchFormDataShouldThrowExceptionWhenDataInvalid()
    {
        // Arrange
        $data = ['invalid' => 'data'];
        $fileLoggerEntity = $this->createMock(FileLoggerService::class);
        $this->validatorService->expects($this->once())
            ->method('validateForSearch')
            ->with($data)
            ->willThrowException(new ValidationException($fileLoggerEntity, 'Invalid data'));

        // Assert & Act
        $this->expectException(ValidationException::class);
        $this->carModelService->validateSearchFormData($data);
    }

    /**
     * @test
     */
    public function searchVehiclesShouldReturnDtoListWhenCarsFound()
    {
        // Arrange
        $filters = ['brand' => 'Toyota'];
        $limit = 10;
        $offset = 0;

        $carModel1 = $this->createMock(CarModel::class);
        $carModel2 = $this->createMock(CarModel::class);

        $carsData = [$carModel1, $carModel2]; // Tableau d'objets CarModel

        $expectedDtoList = [
            $this->createMock(CarDetailsDTO::class),
            $this->createMock(CarDetailsDTO::class)
        ];

        $this->carRepository->expects($this->once())
            ->method('searchVehicles')
            ->with($filters, $limit, $offset)
            ->willReturn($carsData);

        $this->dtoFactory->expects($this->once())
            ->method('createCarListDTO')
            ->with($carsData)
            ->willReturn($expectedDtoList);

        // Act
        $result = $this->carModelService->searchVehicles($filters, $limit, $offset);

        // Assert
        $this->assertSame($expectedDtoList, $result);
    }

    /**
     * @test
     */
    public function searchVehiclesShouldReturnEmptyArrayWhenNoCarsFound()
    {
        // Arrange
        $filters = ['brand' => 'NonExistent'];
        $limit = 10;
        $offset = 0;
        $emptyResult = [];

        $this->carRepository->expects($this->once())
            ->method('searchVehicles')
            ->with($filters, $limit, $offset)
            ->willReturn($emptyResult);

        $this->dtoFactory->expects($this->never())
            ->method('createCarListDTO');

        // Act
        $result = $this->carModelService->searchVehicles($filters, $limit, $offset);

        // Assert
        $this->assertSame($emptyResult, $result);
    }

    /**
     * @test
     */
    public function getTotalFilteredItemsShouldReturnCount()
    {
        // Arrange
        $tableName = 'cars';
        $filters = ['brand' => 'Toyota'];
        $expectedCount = 42;

        $this->carRepository->expects($this->once())
            ->method('countFilteredCarsItems')
            ->with($tableName, $filters)
            ->willReturn($expectedCount);

        // Act
        $result = $this->carModelService->getTotalFilteredItems($tableName, $filters);

        // Assert
        $this->assertSame($expectedCount, $result);
    }

    /**
     * @test
     */
    public function deleteCarShouldReturnBooleanFromRepository()
    {
        // Arrange
        $carId = 123;

        $this->carRepository->expects($this->once())
            ->method('delete')
            ->with($carId)
            ->willReturn(true);

        // Act
        $result = $this->carModelService->deleteCar($carId);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function deleteCarShouldReturnFalseWhenDeletionFails()
    {
        // Arrange
        $carId = 999;

        $this->carRepository->expects($this->once())
            ->method('delete')
            ->with($carId)
            ->willReturn(false);

        // Act
        $result = $this->carModelService->deleteCar($carId);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function deleteCarShouldRethrowExceptionWhenRepositoryThrowsException()
    {
        // Arrange
        $carId = 123;
        $fileLoggerEntity = $this->createMock(FileLoggerService::class);

        $this->carRepository->expects($this->once())
            ->method('delete')
            ->with($carId)
            ->willThrowException(new DataBaseException($fileLoggerEntity, 'Database error'));

        // Assert & Act
        $this->expectException(DataBaseException::class);
        $this->carModelService->deleteCar($carId);
    }

    /**
     * @test
     */
    public function getAllCarsModelsShouldRethrowExceptions()
    {
        // Arrange
        $fileLoggerEntity = $this->createMock(FileLoggerService::class);
        $columns = ['id', 'model'];
        $orderBy = 'id';
        $orderDirection = 'ASC';
        $joins = [];
        $offset = 0;
        $limit = 10;

        $this->carRepository->expects($this->once())
            ->method('findAllPaginated')
            ->willThrowException(new QueryBuilderException($fileLoggerEntity, 'Query builder error'));

        // Assert & Act
        $this->expectException(QueryBuilderException::class);
        $this->carModelService->getAllCarsModels($columns, $orderBy, $orderDirection, $joins, $offset, $limit);
    }
}

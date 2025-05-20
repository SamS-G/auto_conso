<?php

namespace App\Services;

use App\DataTransferObjects\CarDetailsDTO;
use App\Exceptions\DataBaseException;
use App\Exceptions\DTOException;
use App\Exceptions\ValidationException;
use App\Repositories\Interfaces\CarModelRepositoryInterface;
use App\Validation\CarModelSearchValidator;

class CarModelService extends AbstractService
{
    private DTOCarFactoryService $dtoFactory;
    private CarModelSearchValidator $validatorService;
    private CarModelRepositoryInterface $carRepository;

    public function __construct(
        CarModelRepositoryInterface $carRepository,
        CarModelSearchValidator $validatorService,
        DTOCarFactoryService $dtoFactory
    ) {
        parent::__construct($carRepository);

        $this->carRepository = $carRepository;
        $this->validatorService = $validatorService;
        $this->dtoFactory = $dtoFactory;
    }

    /**
     * @return array|CarDetailsDTO
     */
    public function getCarDetailsById(int $id)
    {
        $car = $this->carRepository->findCarDetailsById($id);
        return !empty($car)
            ? $this->dtoFactory->createCarDetailsDTO($car[0])
            : $car;
    }

    /**
     * @throws DTOException
     */
    public function getAllCarsModels(
        array $columns,
        string $orderBy,
        string $orderDirection,
        array $joins,
        int $offset,
        int $limit
    ): array {
        $cars = $this->carRepository->findAllPaginated($columns, $orderBy, $orderDirection, $joins, $offset, $limit);

        return $this->dtoFactory->createCarListDTO($cars);
    }

    /**
     * @throws ValidationException|DataBaseException
     */
    public function validateSearchFormData(array $data): void
    {
        $this->validatorService->validateForSearch($data);
    }

    /**
     * @throws DTOException
     */
    public function searchVehicles(array $filters, int $limit, int $offset): array
    {
        $cars = $this->carRepository->searchVehicles($filters, $limit, $offset);

        return !empty($cars)
            ? $this->dtoFactory->createCarListDTO($cars)
            : $cars;
    }

    public function getTotalFilteredItems(string $tableName, array $filters): int
    {
        // Recherche selon les filtres
        return $this->carRepository->countFilteredCarsItems($tableName, $filters);
    }

    public function deleteCar($id): bool
    {
        return $this->carRepository->delete($id);
    }
}

<?php

namespace App\Services;

use App\DataTransferObjects\CarDetailsDTO;
use App\DataTransferObjects\CarListDTO;
use App\Entities\CarModel;
use App\Exceptions\DTOException;

class DTOCarFactoryService
{
    protected FileLoggerService $logService;

    public function __construct(FileLoggerService $logService)
    {
        $this->logService = $logService;
    }
    public function createCarDetailsDTO(CarModel $car): ?CarDetailsDTO
    {
        return new CarDetailsDTO($car);
    }

    /**
     * @throws DTOException
     */
    public function createCarListDTO(array $cars): ?array
    {
        if (empty($cars)) {
            throw new DTOException($this->logService);
        }
        $carList = [];

        foreach ($cars as $car) {
            $carList[] = new CarListDTO($car);
        }
        return $carList;
    }
}

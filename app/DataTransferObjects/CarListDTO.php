<?php

namespace App\DataTransferObjects;

use App\Entities\CarModel;

class CarListDTO
{
    public string $modelName;
    public ?string $brandName;
    public string $energyType;
    public ?int $id;
    public ?string $transmission;

    public function __construct(CarModel $car)
    {
        $this->brandName = $car->getBrandName();
        $this->id = $car->getId();
        $this->modelName = $car->getModelName();
        $this->transmission = $car->getTransmissionNameByCode($car->getGearboxTypeId());
        $this->energyType = $car->getEnergyType();
    }
}

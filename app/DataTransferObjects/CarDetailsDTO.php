<?php

namespace App\DataTransferObjects;

use App\Entities\CarModel;

class CarDetailsDTO
{
    public string $modelName;
    public ?string $brandName;
    public string $energyType;
    public ?string $energyClass;
    public string $cnit;
    public int $taxPower;
    public int $dinPower;
    public int $kwPower;
    public ?int $mixedConsumption;
    public ?int $extraCityConsumption;
    public ?int $cityConsumption;
    public ?string $transmission;

    public function __construct(CarModel $car)
    {
        $this->brandName = $car->getBrandName();
        $this->cityConsumption = $car->getCityConsumption();
        $this->extraCityConsumption = $car->getExtraCityConsumption();
        $this->mixedConsumption = $car->getMixedConsumption();

        $this->modelName = $car->getModelName();
        $this->energyType = $car->getEnergyType();
        $this->energyClass = $car->getEnergyClass();
        $this->cnit = $car->getCnit();
        $this->transmission = $car->getTransmission();
        $this->taxPower = $car->getTaxPower();
        $this->dinPower = $car->getDinPower();
        $this->kwPower = $car->getKwPower();
    }
}

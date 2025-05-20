<?php

namespace App\Entities;

class ConsumptionData extends AbstractEntity
{
    public static string $table = 'consumption_data';
    protected int $model_id;
    protected float $city_consumption;
    protected float $extra_city_consumption;
    protected float $mixed_consumption;

    // Le constructeur hérité de Base appellera la validation et l'hydratation.
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }
    public function getModelId(): int
    {
        return $this->model_id;
    }


    public function setModelId(int $model_id): void
    {
        $this->model_id = $model_id;
    }

    public function getCityConsumption(): float
    {
        return $this->city_consumption;
    }

    public function setCityConsumption(float $city_consumption): void
    {
        $this->city_consumption = $city_consumption;
    }

    public function getExtraCityConsumption(): float
    {
        return $this->extra_city_consumption;
    }

    public function setExtraCityConsumption(float $extra_city_consumption): void
    {
        $this->extra_city_consumption = $extra_city_consumption;
    }

    public function getMixedConsumption(): float
    {
        return $this->mixed_consumption;
    }

    public function setMixedConsumption(float $mixed_consumption): void
    {
        $this->mixed_consumption = $mixed_consumption;
    }

}

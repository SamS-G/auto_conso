<?php

namespace App\Entities;

use App\Database\Traits\Hydratable;
use App\Services\FileLoggerService;

class CarModel extends AbstractEntity
{
    use Hydratable;

    public static string $table = 'car_model';
    protected ?string $model_name;
    protected ?string $energy_type;
    protected ?int $brand_id;
    protected ?string $brand_name = null; // Lié à brand_id de la table brand
    protected ?array $consumption_id;
    protected ?int $cityConsumption = null; // Lié à la table consumption_data
    protected ?int $extraCityConsumption = null; // Lié à la table consumption_data
    protected ?int $mixedConsumption = null; // Lié à la table consumption_data
    // Lié à la table consumption_data
    protected ?string $cnit;
    protected ?int $gearbox_type_id;
    protected ?int $tax_power;
    protected ?int $din_power;
    protected ?int $kw_power;
    public const ESSENCE = 'ES';
    public const DIESEL = 'GO';
    public const FLEX = 'FE';
    public const GPL = 'GP/ES';
    public const HYBRID = 'HYB';
    public const ELEC = 'ELEC';

    public const TRANSMISSION_AUTOMATIC = 1; // AUTO
    public const TRANSMISSION_MANUAL = 2; // MAN
    public const TRANSMISSION_CVT = 3; // CVT
    protected ?string $energyClass;

    public const A = 1;
    public const B = 2;
    public const C = 3;
    public const D = 4;
    public const E = 5;
    public const F = 6;
    public const G = 7;
    /**
     * @var FileLoggerService|mixed|null
     */
    protected $fileLogger;


    public function __construct(
        array $data = [],
        $fileLoggerService = null
    ) {
        $this->fileLogger = $fileLoggerService;
        parent::__construct($data, $fileLoggerService); // Appelle le constructeur de la classe Base qui hydrate
    }

    public static function getEnergyClasses(): array
    {
        return [
            self::A,
            self::B,
            self::C,
            self::D,
            self::E,
            self::F,
            self::G,
        ];
    }
    public static function getEnergyTypes(): array
    {
        return [
            self::ESSENCE,
            self::DIESEL,
            self::FLEX,
            self::GPL,
            self::HYBRID,
            self::ELEC
        ];
    }

    /**
     * Retourne le nom complet de l'énergie d'après le code
     * @param string $energy
     * @return string|null
     */
    public static function getEnergyTypeNameByCode(string $energy): ?string
    {
        switch ($energy) {
            case self::ESSENCE:
                return 'Essence';
            case self::DIESEL:
                return 'Gasoil';
            case self::FLEX:
                return 'Flex-Fuel';
            case self::GPL:
                return 'GPL-Essence';
            case self::HYBRID:
                return 'Hybride';
            case self::ELEC:
                return 'Electric';
            default:
                return null;
        }
    }
    public static function getTransmissionNameByCode($code): ?string
    {
        switch ($code) {
            case self::TRANSMISSION_AUTOMATIC:
                return 'Automatique';
            case self::TRANSMISSION_MANUAL:
                return 'Manuelle';
            case self::TRANSMISSION_CVT:
                return 'Variation continue';
            default:
                return null;
        }
    }
    public function getEnergyType(): ?string
    {
        return $this->energy_type;
    }

    public function setEnergyType(?string $energy_type): void
    {
        $this->energy_type = $energy_type;
    }

    public function getEnergyClass(): ?string
    {
        return $this->energyClass;
    }

    public function setEnergyClass(?string $energyClass): void
    {
        $this->energyClass = $energyClass;
    }

    public function getGearboxTypeId(): int
    {
        return $this->gearbox_type_id;
    }

    public function setGearboxTypeId(int $gearbox_type_id): void
    {
        $this->gearbox_type_id = $gearbox_type_id;
    }

    public function getTaxPower(): int
    {
        return $this->tax_power;
    }

    public function setTaxPower(int $tax_power): void
    {
        $this->tax_power = $tax_power;
    }

    public function getDinPower(): int
    {
        return $this->din_power;
    }

    public function setDinPower(int $din_power): void
    {
        $this->din_power = $din_power;
    }

    public function getKwPower(): int
    {
        return $this->kw_power;
    }

    public function setKwPower(int $kw_power): void
    {
        $this->kw_power = $kw_power;
    }


    public function getModelName(): string
    {
        return $this->model_name;
    }

    public function setModelName(string $model_name): void
    {
        $this->model_name = $model_name;
    }

    public function getBrandId(): int
    {
        return $this->brand_id;
    }

    public function setBrandId(int $brand_id): void
    {
        $this->brand_id = $brand_id;
    }

    public function getConsumptionId(): ?array
    {
        return $this->consumption_id;
    }

    public function setConsumptionId(?array $consumption_id): void
    {
        $this->consumption_id = $consumption_id;
    }

    public function getCnit(): string
    {
        return $this->cnit;
    }

    public function setCnit(string $cnit): void
    {
        $this->cnit = $cnit;
    }

// Foreign key lié = brand_id
    public function getBrandName(): ?string
    {
        return $this->brand_name;
    }

    public function setBrandName(?string $brand_name): void
    {
        $this->brand_name = $brand_name;
    }

    /**
     * @return int|null
     */
    public function getCityConsumption(): ?int
    {
        return $this->cityConsumption;
    }

    /**
     * @param int|null $cityConsumption
     */
    public function setCityConsumption(?int $cityConsumption): void
    {
        $this->cityConsumption = $cityConsumption;
    }

    /**
     * @return int|null
     */
    public function getExtraCityConsumption(): ?int
    {
        return $this->extraCityConsumption;
    }

    /**
     * @param int|null $extraCityConsumption
     */
    public function setExtraCityConsumption(?int $extraCityConsumption): void
    {
        $this->extraCityConsumption = $extraCityConsumption;
    }

    /**
     * @return int|null
     */
    public function getMixedConsumption(): ?int
    {
        return $this->mixedConsumption;
    }

    /**
     * @param int|null $mixedConsumption
     */
    public function setMixedConsumption(?int $mixedConsumption): void
    {
        $this->mixedConsumption = $mixedConsumption;
    }

    protected ?string $transmission = null;

    /**
     * @return string|null
     */
    public function getTransmission(): ?string
    {
        return $this->transmission;
    }

    /**
     * @param string|null $transmission
     */
    public function setTransmission(?string $transmission): void
    {
        $this->transmission = $transmission;
    }
}

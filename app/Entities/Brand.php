<?php

namespace App\Entities;

use App\Database\Traits\Hydratable;
use App\Services\FileLoggerService;

class Brand extends AbstractEntity
{
    use Hydratable;

    public static string $table = 'brand';
    protected ?string $brand_name;
    protected ?string $nationality;
    public const COUNTRY_JAPAN = 'JP'; // Japon
    public const COUNTRY_GERMANY = 'DE'; // Allemagne
    public const COUNTRY_UNITED_STATES = 'US'; // États-Unis
    public const COUNTRY_CHINA = 'CN'; // Chine
    public const COUNTRY_SOUTH_KOREA = 'KR'; // Corée du Sud
    public const COUNTRY_FRANCE = 'FR'; // France
    public const COUNTRY_ITALY = 'IT'; // Italie
    public const COUNTRY_UNITED_KINGDOM = 'GB'; // Royaume-Uni
    public const COUNTRY_SPAIN = 'ES'; // Espagne
    public const COUNTRY_CANADA = 'CA'; // Canada
    public const COUNTRY_INDIA = 'IN'; // Inde
    public const COUNTRY_MEXICO = 'MX'; // Mexique
    public const COUNTRY_THAILAND = 'TH'; // Thaïlande
    public const COUNTRY_BRAZIL = 'BR'; // Brésil
    public const COUNTRY_SWEDEN = 'SW'; // Suede
    public const COUNTRY_SLOVAKIA = 'SK'; // Slovaquie
    public const COUNTRY_RUSSIA = 'RU'; // Slovaquie

    // Le constructeur hérité de Base appellera l'hydratation.
    public function __construct(array $data = [], $fileLoggerService = null)
    {
        parent::__construct($data, $fileLoggerService);
    }

    public function getBrandName(): string
    {
        return $this->brand_name;
    }

    public function getNationality(): string
    {
        return $this->nationality;
    }

    protected function setBrandName(string $brand_name): void
    {
        $this->brand_name = $brand_name;
    }

    protected function setNationality(string $nationality): void
    {
        $this->nationality = $nationality;
    }
}

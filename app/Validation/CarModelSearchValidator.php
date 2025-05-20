<?php

namespace App\Validation;

use App\Entities\CarModel;
use App\Exceptions\DataBaseException;
use App\Exceptions\ValidationException;
use App\Repositories\BrandRepository;
use App\Repositories\GearboxTypeRepository;
use App\Repositories\Interfaces\RepositoryInterface;
use App\Services\FileLoggerService;
use App\Services\Interfaces\LoggerInterface;

class CarModelSearchValidator extends AbstractValidator
{
    private RepositoryInterface $brandRepository;
    private RepositoryInterface $gearBoxTypeRepository;

    public function __construct(
        RepositoryInterface $brandRepository,
        RepositoryInterface $gearBoxTypeRepository,
        LoggerInterface $fileLoggerService
    ) {
        parent::__construct($fileLoggerService);
        $this->brandRepository = $brandRepository;
        $this->gearBoxTypeRepository = $gearBoxTypeRepository;
    }

    /**
     * @param array $data Tableau des filtres demandés par l'utilisateur pour recherche
     * @return void
     * @throws ValidationException|DataBaseException
     */
    public function validateForSearch(array $data): void
    {
        // Pour la recherche, certains champs peuvent être optionnels
        if (isset($data['brand_id']) && !$this->isEmpty($data['brand_id'])) {
            $this->validateBrandId($data);
        }
        if (isset($data['gearbox_type_id']) && !$this->isEmpty($data['gearbox_type_id'])) {
            $this->validateGearboxTypeId($data);
        }
        if (isset($data['energy_type']) && !$this->isEmpty($data['energy_type'])) {
            $this->validateEnergyType($data);
        }
    }

    /**
     * @throws ValidationException
     * @throws DataBaseException
     */
    private function validateBrandId(array $data): void
    {
        if (!isset($data['brand_id']) || $this->isEmpty($data['brand_id'])) {
            $this->throwError('brand_id', 'L\'ID de la marque est requis.');
        } elseif (!ctype_digit((string)$data['brand_id']) || (int)$data['brand_id'] <= 0) {
            $this->throwError('brand_id', 'L\'ID de la marque doit être un entier positif.');
        } elseif (!$this->brandRepository->findById((int)$data['brand_id'])) {
            $this->throwError('brand_id', 'La marque spécifiée n\'existe pas.');
        }
    }

    /**
     * @throws ValidationException
     * @throws DataBaseException
     */
    private function validateGearboxTypeId(array $data): void
    {
        if (!isset($data['gearbox_type_id']) || $this->isEmpty($data['gearbox_type_id'])) {
            $this->throwError('gearbox_type_id', 'L\'ID du type de boîte de vitesses est requis.');
        } elseif (!ctype_digit((string)$data['gearbox_type_id']) || (int)$data['gearbox_type_id'] <= 0) {
            $this->throwError('gearbox_type_id', 'L\'ID du type de boîte de vitesses doit être un entier positif.');
        } elseif (
            !$this->gearBoxTypeRepository->findById((int)$data['gearbox_type_id'])
        ) {
            $this->throwError('gearbox_type_id', 'Le type de boîte de vitesses spécifié n\'existe pas.');
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateEnergyType(array $data): void
    {
        if (
            !isset($data['energy_type']) ||
            !in_array($data['energy_type'], CarModel::getEnergyTypes(), true)
        ) {
            $this->throwError(
                'energy_type',
                sprintf(
                    'Le type d\'énergie n\'est pas l\'une des valeurs attendues, elle est => %s',
                    $data['energy_type']
                )
            );
        }
    }
}

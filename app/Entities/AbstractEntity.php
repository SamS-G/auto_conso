<?php

namespace App\Entities;

use App\Database\Traits\Hydratable;
use App\Exceptions\HydratationException;
use App\Services\FileLoggerService;
use DateTime;

abstract class AbstractEntity
{
    use Hydratable;

// évite les problèmes d'instanciation et différencie un objet existant d'un en cours de création
    private ?int $id = null;
    private ?DateTime $created_at = null;
    private ?DateTime $updated_at = null;
    private ?FileLoggerService $fileLogger;

    /**
     * Constructeur de la classe de base.
     * Valide et hydrate l'objet si des données sont fournies.
     * Initialise les dates de création et de mise à jour pour les nouvelles entités.
     *
     * @param array $data Tableau associatif des données pour l'hydratation.
     * @throws HydratationException
     */
    public function __construct(array $data = [], FileLoggerService $fileLoggerService = null)
    {
        // Valide les données avant l'hydratation
        if (!empty($data) && $fileLoggerService) {
            $this->fileLogger = $fileLoggerService;
            $this->initializeHydratableTrait($fileLoggerService);
            $this->hydrate($data); // Hydrate l'objet
        }
        // Initialise created_at si l'objet est nouveau
        if ($this->created_at === null) {
            $this->created_at = new DateTime();
        }
         // Met à jour updated_at à la création (et à chaque nouvelle instance)
        $this->updated_at = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): ?int
    {
        return $this->id = $id;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(): void
    {
        $this->updated_at = new DateTime();
    }
}

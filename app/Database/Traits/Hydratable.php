<?php

namespace App\Database\Traits;

use App\Exceptions\HydratationException;
use App\Helpers\StringHelper;
use App\Services\FileLoggerService;
use DateTime;
use InvalidArgumentException;
use Throwable;

/**
 * 'Static' = fait référence à la class appelée au moment de l'exécution.
 * 'Self', fait référence à la class du trait.
 */
trait Hydratable
{
    protected function initializeHydratableTrait(FileLoggerService $fileLoggerService): void
    {
        $this->fileLogger = $fileLoggerService;
    }

    /**
     * Hydrate un modèle à partir d'un tableau associatif
     * — transforme le nom de l'attribut en camelCase
     * — si la méthode existe, elle est appelée avec la valeur de son argument
     * — sinon, et si l'attribut existe dans le modèle, la valeur lui est assignée
     * $this = l'instance de la class dans laquelle la méthode du trait est appelée
     * @param array $data
     * @return self
     * @throws InvalidArgumentException Si la date fournie est malformée.
     * @throws HydratationException
     */
    private function hydrate(array $data): self
    {
        foreach ($data as $attribute => $value) {
            try {
                // Si l'attribut est une date et qu'il contient une valeur
                if (
                    ($attribute === 'created_at' || $attribute === 'updated_at') &&
                    $value !== null
                ) {
                    // Convertir la chaîne en objet DateTime
                    try {
                        $this->{$attribute} = new DateTime($value);
                        continue;
                    } catch (Throwable $e) {
                        throw new HydratationException(
                            $this->fileLogger,
                            "La chaîne de date '{$value}' pour l'attribut '{$attribute}' est malformée.",
                            [],
                            0,
                            $e
                        );
                    }
                }

                $attrToCamel = StringHelper::snakeCaseToCamelCase($attribute);
                $method = sprintf("%s$attrToCamel", 'set');

                if (method_exists($this, $method)) {
                    $this->{$method}($value);
                } elseif (property_exists($this, $attribute)) {
                    $this->{$attribute} = $value;
                }
            } catch (InvalidArgumentException $e) {
                throw new HydratationException(
                    $this->fileLogger,
                    'L\'objet n\'a pu être correctement hydraté',
                    [],
                    0,
                    $e
                );
            }
        }
        return $this;
    }

    /**
     * Création d'une instance de l'objet et l'hydrate
     * @param array $data
     * @return static
     * @throws HydratationException Si une date fournie est malformée.
     */
    public function make(array $data)
    {
        try {
            return (new static())->hydrate($data);
        } catch (InvalidArgumentException $e) {
            throw new HydratationException(
                $this->fileLogger,
                'L\'objet n\'a pu être correctement hydraté',
                [],
                0,
                $e
            );
        }
    }
}

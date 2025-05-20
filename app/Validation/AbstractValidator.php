<?php

namespace App\Validation;

use App\Exceptions\ValidationException;
use App\Services\FileLoggerService;
use App\Services\Interfaces\LoggerInterface;

class AbstractValidator
{
    protected FileLoggerService $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Lance une ValidationException avec le champ et le message d'erreur.
     *
     * @param string $field Le nom du champ.
     * @param string $message Le message d'erreur.
     * @throws ValidationException
     */
    protected function throwError(string $field, string $message): void
    {
        throw new ValidationException($this->logger, "Erreur de validation pour le champ '{$field}': {$message}");
    }

    /**
     * Vérifie si le tableau de données contient une clé.
     *
     * @param array $data Le tableau de données.
     * @param string $key La clé à vérifier.
     * @return bool
     */
    protected function hasKey(array $data, string $key): bool
    {
        return array_key_exists($key, $data);
    }

    /**
     * Vérifie si une valeur est vide.
     *
     * @param mixed $value La valeur à vérifier
     * @return bool
     */
    protected function isEmpty($value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }
}

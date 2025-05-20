<?php

namespace App\Core;

use Exception;
use ReflectionClass;
use ReflectionParameter;

/**
 * Classe de gestion des dépendances avec injection automatique
 * Permet d'enregistrer, résoudre et gérer des dépendances avec support des singletons
 */
class DependencyInjector
{
    /** @var array Stockage des définitions de dépendances */
    private array $dependencies = [];

    /** @var array Cache des instances singleton */
    public array $singletons = [];

    /**
     * Enregistre une dépendance dans le conteneur
     *
     * @param string $name Nom de la dépendance (généralement un nom de classe)
     * @param callable $resolver Fonction de création de l'instance
     * @param bool $singleton Si true, l'instance sera réutilisée
     * @param string|null $id Identifiant optionnel pour différencier plusieurs implémentations
     */
    public function register(string $name, callable $resolver, bool $singleton = false, ?string $id = null): void
    {
        $actualId = $id ?? 'default';
        $this->dependencies[$name][$actualId] = [
            'resolver' => $resolver,
            'singleton' => $singleton
        ];

        // Si un ID spécifique est fourni, créer une référence pour y accéder directement
        if ($id !== null && $id !== 'default') {
            $this->dependencies[$id] = &$this->dependencies[$name][$actualId]; // permet un accès direct à l'ID
        }
    }

    /**
     * Résout une dépendance et retourne une instance
     *
     * @param string $className Nom de la classe à résoudre
     * @param string $id Identifiant spécifique de l'implémentation
     * @param array $constructorArguments Arguments à passer explicitement au constructeur
     * @return object Instance résolue
     * @throws Exception Si la résolution échoue
     */
    public function resolve(string $className, string $id = 'default', array $constructorArguments = []): object
    {
        try {
            // Essayer de résoudre depuis les dépendances enregistrées
            $instance = $this->tryResolveRegistered($className, $id);
            if ($instance !== null) {
                return $instance;
            }

            // Sinon, créer une nouvelle instance en résolvant récursivement ses dépendances
            return $this->resolveNew($className, $constructorArguments);
        } catch (Exception $e) {
            $this->logError("Erreur lors de la résolution de '{$className}' avec l'ID '{$id}': " . $e->getMessage());
            throw new Exception(
                "Erreur lors de la résolution de '{$className}' avec l'ID '{$id}': " .
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Tente de résoudre une dépendance déjà enregistrée
     *
     * @param string $className Nom de la classe
     * @param string $id Identifiant
     * @return object|null Instance résolue ou null si non trouvée
     * @throws Exception
     */
    private function tryResolveRegistered(string $className, string $id): ?object
    {
        // Cas 1 : Dépendance enregistrée avec l'ID spécifié
        if (isset($this->dependencies[$className][$id])) {
            return $this->resolveRegistered($className, $id);
        }

        // Cas 2 et 3 : Dépendance enregistrée avec l'ID comme nom de classe
        // ou dépendance enregistrée avec ID par défaut
        return $this->tryResolveRegisteredById($className);
    }

    /**
     * Résout une dépendance enregistrée
     *
     * @param string $name Nom de la dépendance
     * @param string $id Identifiant de l'implémentation
     * @return object Instance résolue
     * @throws Exception Si la résolution échoue
     */
    private function resolveRegistered(string $name, string $id): object
    {
        if (!isset($this->dependencies[$name][$id])) {
            $this->logError(
                "Impossible de résoudre la dépendance '{$name}' avec l'ID '{$id}'. L'enregistrement n'existe pas."
            );
            throw new Exception("Impossible de résoudre la dépendance '{$name}' avec l'ID '{$id}'.");
        }

        $dependency = $this->dependencies[$name][$id];
        $singletonKey = $name . $id;

        try {
            // Retourner l'instance singleton existante ou en créer une nouvelle
            if ($dependency['singleton']) {
                return $this->singletons[$singletonKey] ??= $dependency['resolver']();
            }
            // Créer une nouvelle instance à chaque fois
            return $dependency['resolver']();
        } catch (Exception $e) {
            $this->logError(
                "Erreur lors de la création de l'instance pour '{$name}' avec l'ID '{$id}': " . $e->getMessage()
            );
            throw new Exception("Erreur lors de la création de l'instance pour '{$name}'", $e->getCode(), $e);
        }
    }

    /**
     * Crée une nouvelle instance d'une classe en résolvant ses dépendances
     *
     * @param string $className Nom de la classe à instancier
     * @param array $constructorArguments Arguments explicites pour le constructeur
     * @return object Instance créée
     * @throws Exception Si la création échoue
     */
    private function resolveNew(string $className, array $constructorArguments): object
    {
        try {
            $reflectionClass = new ReflectionClass($className);
            $constructor = $reflectionClass->getConstructor();

            // Si pas de constructeur, simple instanciation
            if (!$constructor) {
                return $reflectionClass->newInstance();
            }

            // Résoudre les arguments du constructeur
            $resolvedArguments = $this->resolveConstructorArguments(
                $constructor->getParameters(),
                $className,
                $constructorArguments
            );

            return $reflectionClass->newInstanceArgs($resolvedArguments);
        } catch (Exception $e) {
            $this->logError("Erreur lors de la résolution des dépendances pour '{$className}': " . $e->getMessage());
            throw new Exception(
                "Erreur lors de la résolution des dépendances pour '{$className}': " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Résout les arguments du constructeur d'une classe
     *
     * @param array $parameters Paramètres du constructeur
     * @param string $className Nom de la classe
     * @param array $constructorArguments Arguments explicites
     * @return array Arguments résolus
     * @throws Exception Si la résolution échoue
     */
    private function resolveConstructorArguments(
        array $parameters,
        string $className,
        array $constructorArguments
    ): array {
        return array_map(
            function (ReflectionParameter $parameter) use ($className, $constructorArguments) {
                $parameterName = $parameter->getName();

                // Cas 1 : Utiliser l'argument explicite s'il existe
                if (array_key_exists($parameterName, $constructorArguments)) {
                    return $constructorArguments[$parameterName];
                }

                // Obtenir le type du paramètre s'il existe
                $dependencyName = null;
                $type = $parameter->getType();
                if ($type !== null) {
                    $dependencyName = $type->getName();
                }

                // Cas 2 : Résoudre par type
                if ($dependencyName !== null) {
                    $instance = $this->tryResolveDependencyByType($dependencyName, $parameterName);
                    if ($instance !== null) {
                        return $instance;
                    }
                }

                // Cas 3 : Résoudre par nom de paramètre
                $instance = $this->tryResolveDependencyByName($parameterName);
                if ($instance !== null) {
                    return $instance;
                }

                // Cas 4 : Utiliser la valeur par défaut si disponible
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }

                // Échec de résolution
                $this->logError(
                    "Impossible de résoudre la dépendance '{$parameterName}' pour la classe '{$className}'."
                );
                throw new Exception("Impossible de résoudre la dépendance '{$parameterName}' pour '{$className}'.");
            },
            $parameters
        );
    }

    /**
     * Tente de résoudre une dépendance par son type
     *
     * @param string $dependencyName Type de la dépendance
     * @param string $parameterName Nom du paramètre
     * @return object|null Instance ou null si non trouvée
     * @throws Exception
     */
    private function tryResolveDependencyByType(string $dependencyName, string $parameterName): ?object
    {
        // Par type avec ID default
        if (isset($this->dependencies[$dependencyName]['default'])) {
            return $this->resolveRegistered($dependencyName, 'default');
        }

        // Par type avec ID = nom du paramètre
        if (isset($this->dependencies[$dependencyName][$parameterName])) {
            return $this->resolveRegistered($dependencyName, $parameterName);
        }

        return null;
    }

    /**
     * Tente de résoudre une dépendance par le nom du paramètre
     *
     * @param string $parameterName Nom du paramètre
     * @return object|null Instance ou null si non trouvée
     * @throws Exception
     */
    private function tryResolveDependencyByName(string $parameterName): ?object
    {
        // Par nom de paramètre comme clé principale avec premier ID disponible
        return $this->tryResolveRegisteredById($parameterName);
    }

    /**
     * Initialise toutes les dépendances singleton
     * Utile pour vérifier la validité des dépendances au démarrage de l'application
     *
     * @throws Exception Si l'initialisation échoue
     */
    public function initializeDependencies(): void
    {
        foreach ($this->dependencies as $name => $implementations) {
            foreach ($implementations as $id => $dependency) {
                if ($dependency['singleton'] && !isset($this->singletons[$name . $id])) {
                    try {
                        $this->singletons[$name . $id] = $dependency['resolver']();
                    } catch (Exception $e) {
                        $this->logError(
                            "Erreur lors de l'initialisation du Singleton '{$name}' avec l'ID '{$id}': " . $e->getMessage()
                        );
                        throw new Exception(
                            "Erreur lors de l'initialisation du Singleton '{$name}'",
                            $e->getCode(),
                            $e
                        );
                    }
                }
            }
        }
    }

    /**
     * Enregistre une erreur dans le fichier de log
     *
     * @param string $message Message d'erreur
     */
    private function logError(string $message): void
    {
        error_log(
            'Erreur DI : ' . $message . "\n",
            3,
            dirname(__DIR__, 2) . '/logs/dependency_errors.log'
        );
    }

    /**
     * @param string $className
     * @return object|null
     * @throws Exception
     */
    private function tryResolveRegisteredById(string $className): ?object
    {
        // Cas 2 : ID = par nom de class
        if (isset($this->dependencies[$className]) && is_array($this->dependencies[$className])) {
            $firstKey = array_key_first($this->dependencies[$className]);
            if ($firstKey !== null) {
                return $this->resolveRegistered($firstKey, $className);
            }
        }

        // Cas 3 : ID = nom par défaut
        if (isset($this->dependencies[$className]) && !is_array($this->dependencies[$className])) {
            return $this->resolveRegistered($className, 'default');
        }

        return null;
    }
}

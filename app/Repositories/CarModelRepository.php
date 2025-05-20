<?php

namespace App\Repositories;

use App\Database\QueryBuilder;
use App\Entities\CarModel;
use App\Exceptions\DataBaseException;
use App\Exceptions\QueryBuilderException;
use App\Repositories\Interfaces\CarModelRepositoryInterface;
use App\Repositories\Interfaces\RepositoryInterface;
use App\Services\FileLoggerService;
use App\Services\PdoService;
use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

class CarModelRepository extends AbstractRepository implements CarModelRepositoryInterface
{
    private FileLoggerService $fileLogger;

    public function __construct(
        PdoService $pdoService,
        QueryBuilder $queryBuilder,
        FileLoggerService $fileLoggerService
    ) {
        $this->fileLogger = $fileLoggerService;
        parent::__construct($pdoService, $queryBuilder, $fileLoggerService);
    }

    /**
     * Définit le nom de la classe d'entité gérée par ce repository.
     * @return string
     */
    protected static function getEntityClassName(): string
    {
        return CarModel::class;
    }

    /**
     * Définit le nom de la table de base de données associée à l'entité CarModel.
     * @return string
     */
    protected static function getTableName(): string
    {
        return CarModel::$table;
    }

    /**
     * Méthode pour extraire les données d'une entité CarModel sous forme de tableau
     * associatif, prêt à être utilisé pour l'insertion ou la mise à jour en base de données.
     * @param object $entity
     * @return array
     */
    protected function getEntityData(object $entity): array
    {
        // Vérification du type de l'entité pour s'assurer qu'il s'agit bien d'un CarModel.
        if (!$entity instanceof CarModel) {
            throw new InvalidArgumentException("L'entité doit être une instance de " . CarModel::class);
        }
        return [
            'name' => $entity->getModelName(),
            'brand_id' => $entity->getBrandId(),
            'cnit' => $entity->getCnit(),
            'energy_class' => $entity->getEnergyClasses(),
            'gearbox_type_id' => $entity->getGearboxTypeId(),
            'tax_power' => $entity->getTaxPower(),
            'din_power' => $entity->getDinPower(),
            'kw_power' => $entity->getKwPower(),
        ];
    }

    /**
     * Recherche des véhicules en fonction de filtres, avec pagination.
     * @param array $filters Tableau associatif des filtres (ex: ['brand_id' => 1, 'energy_type' => 'Essence']).
     * @param int $limit Nombre maximum de résultats par page (par défaut 10).
     * @param int $offset Indice de départ pour la pagination (par défaut 0).
     * @return array Un tableau d'entités CarModel correspondant aux critères, ou null en cas d'erreur.
     * @throws DataBaseException
     */
    public function searchVehicles(array $filters, int $limit = 10, int $offset = 0): array
    {
        $table = 'car_model';

        // Colonnes à sélectionner de la table principale.
        $columns = ['id', 'model_name', 'energy_type', 'brand_id', 'energy_class', 'gearbox_type_id'];
        // Configurations pour les jointures avec d'autres tables.
        $joins = [
            'brand_id' => ['table' => 'brand', 'display' => ['brand_name']],
            'gearbox_type_id' => ['table' => 'gearbox_type', 'display' => ['transmission']]
        ];

        try {
            // Construction des conditions WHERE à partir des filtres fournis.
            $whereConditions = $this->buildWhereCarConditions($filters);
            // Préparation des colonnes pour la clause SELECT, incluant les alias pour les colonnes jointes.
            $selectedColumns = $this->queryBuilder->prepareColumns($table, $columns, $joins);
            // Construction de la clause WHERE à partir des conditions préparées.
            $whereClause = $this->queryBuilder->buildWhereClause($whereConditions);
            // Construction des clauses JOIN à partir de la configuration des jointures.
            $joinClause = $this->queryBuilder->buildJoinClauses($table, $joins);

            // Construction de la requête SQL paginée.
            $sql = $this->queryBuilder->buildPaginatedQuery(
                $table,
                $selectedColumns,
                $joinClause,
                $whereClause,
                'brand_id'
            );

            $stmt = $this->pdo->prepare($sql);
            // Binding dynamique des valeurs pour les conditions WHERE.
            foreach ($whereConditions as $filter) {
                foreach ($filter['condition'] as $key => $val) {
                    $stmt->bindValue(":{$key}", $val);
                }
            }

            // Binding des valeurs pour la pagination (LIMIT et OFFSET).
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                return $this->createEntitiesFromRows($rows);
            }
            return [];
        } catch (PDOException $e) {
            throw new DataBaseException(
                $this->fileLogger,
                "Erreur lors de la récupération des véhicules filtrés dans la base de données.",
                [],
                0,
                $e
            );
        } catch (QueryBuilderException $e) {
            throw new DataBaseException(
                $this->fileLogger,
                "Erreur lors de la construction de la requête pour le comptage des véhicules filtrés.",
                [],
                0,
                $e
            );
        }
    }

    /**
     * Récupère tous les détails liés à un véhicule par son ID en utilisant un tableau d'options.
     * @param int $id L'ID du véhicule à chercher.
     * @param array $options Tableau optionnel pour surcharger les colonnes, les jointures et les conditions.
     * @Format : ['columns' → [...], 'joins' → [...], 'where' → [...], 'orderBy' → '... ',
     * 'orderDirection' → 'ASC|DESC'].
     * @return array Un tableau d'entités CarModel correspondant à l'ID, ou null en cas d'erreur.
     * @throws RuntimeException En cas d'erreur lors de l'exécution de la requête ou de la récupération des résultats.
     * @throws DataBaseException
     */
    public function findCarDetailsById(
        int $id,
        array $options = []
    ): array {
        $tableName = $this->getTableName();
        $selectedColumns = $options['columns'] ?? [
            'model_name',
            'energy_type',
            'cnit',
            'tax_power',
            'din_power',
            'kw_power',
            'energy_class',
        ];
        $joinsConfig = $options['joins'] ?? [
            'consumption_data_id' => [
                'table' => 'consumption_data',
                'display' => ['city_consumption', 'extra_city_consumption', 'mixed_consumption']
            ],
            'brand_id' => [
                'table' => 'brand',
                'display' => ['brand_name']
            ],
            'gearbox_type_id' => [
                'table' => 'gearbox_type',
                'display' => ['transmission']
            ]
        ];
        $whereConditions = $options['where'] ?? [['table' => $tableName, 'condition' => ['id' => $id]]];
        $orderByColumn = $options['orderBy'] ?? null;
        $orderDirection = $options['orderDirection'] ?? 'ASC';

        try {
            // Préparation des colonnes pour la clause SELECT.
            $columns = $this->queryBuilder->prepareColumns($tableName, $selectedColumns, $joinsConfig);
            // Construction de la clause WHERE.
            $where = $this->queryBuilder->buildWhereClause($whereConditions);
            // Construction des clauses JOIN.
            $join = $this->queryBuilder->buildJoinClauses($tableName, $joinsConfig);
            // Construction de la clause ORDER BY.
            $orderBy = $this->queryBuilder->buildOrderByClause($tableName, $orderByColumn, $orderDirection);

            // Construction de la requête SQL complète.
            $query = $this->queryBuilder->buildQuery($tableName, $columns, $join, $where, $orderBy);

            $stmt = $this->pdo->prepare($query);

            // Binding dynamique des paramètres WHERE.
            if (!empty($whereConditions)) {
                foreach ($whereConditions as $conditionGroup) {
                    foreach ($conditionGroup['condition'] as $key => $value) {
                        $paramName = ':' . $key;
                        $stmt->bindValue($paramName, $value);
                    }
                }
            } else {
                // Liaison de l'ID si la clause WHERE par défaut est utilisée.
                $stmt->bindParam(':id', $id);
            }

            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                return $this->processEntityDetails(
                    $rows,
                    'energyType',
                    'getEnergyType',
                    'getEnergyTypeNameByCode',
                    'setEnergyType'
                );
            }
            return [];
        } catch (QueryBuilderException $e) {
            throw new DataBaseException(
                $this->fileLogger,
                "Erreur lors de la construction de la requête pour le comptage des véhicules filtrés.",
                [],
                0,
                $e
            );
        } catch (PDOException $e) {
            throw new DataBaseException(
                $this->fileLogger,
                "Erreur lors de la récupération des détails du véhicule depuis la base de données.",
                [],
                0,
                $e
            );
        } catch (Exception $e) {
            throw new RuntimeException(
                "Une erreur inattendue s'est produite lors de la récupération des détails du véhicule : "
                . $e->getMessage()
            );
        }
    }
    /**
     * Méthode utilitaire pour construire les conditions WHERE spécifiques à la recherche de véhicules.
     * @param array $filters Les filtres demandés par l'utilisateur
     * @return array
     */
    private function buildWhereCarConditions(array $filters): array
    {
        $conditions = [];

        // Ajout d'une condition pour le filtre 'brand_id' s'il est présent.
        if (!empty($filters['brand_id'])) {
            $conditions[] = [
                'table' => 'car_model',
                'condition' => ['brand_id' => $filters['brand_id']]
            ];
        }

        // Ajout d'une condition pour le filtre 'energy_type' s'il est présent.
        if (!empty($filters['energy_type'])) {
            $conditions[] = [
                'table' => 'car_model',
                'condition' => ['energy_type' => $filters['energy_type']]
            ];
        }

        // Ajout d'une condition pour le filtre 'energy_class' s'il est présent.
        if (!empty($filters['energy_class'])) {
            $conditions[] = [
                'table' => 'car_model',
                'condition' => ['energy_class' => $filters['energy_class']]
            ];
        }

        // Ajout d'une condition pour le filtre 'gearbox_type_id' s'il est présent.
        if (!empty($filters['gearbox_type_id'])) {
            $conditions[] = [
                'table' => 'car_model',
                'condition' => ['gearbox_type_id' => $filters['gearbox_type_id']]
            ];
        }
        return $conditions;
    }

    /**
     * Méthode pour compter le nombre total d'éléments de voiture correspondant aux filtres.
     *
     * @param string $tableName La table sur laquelle compter
     * @param array $filters Les filtres demandés par l'utilisateur
     * @return int
     * @throws DataBaseException
     */
    public function countFilteredCarsItems(string $tableName, array $filters): int
    {
        try {
            // Configurations pour les jointures nécessaires au comptage.
            $joins = [
                'brand_id' => ['table' => 'brand', 'display' => ['brand_name']],
                'gearbox_type_id' => ['table' => 'gearbox_type', 'display' => ['transmission']]
            ];

            // Construction des clauses JOIN.
            $joinClause = $this->queryBuilder->buildJoinClauses($tableName, $joins);
            // Construction des conditions WHERE à partir des filtres.
            $whereConditions = $this->buildWhereCarConditions($filters);
            // Construction de la clause WHERE.
            $whereClause = $this->queryBuilder->buildWhereClause($whereConditions);

            // Construction de la requête SQL pour compter les éléments.
            $sql = "SELECT COUNT(*) FROM {$tableName} {$joinClause} {$whereClause}";

            $stmt = $this->pdo->prepare($sql);

            // Binding dynamique des valeurs pour les conditions WHERE.
            foreach ($whereConditions as $filter) {
                foreach ($filter['condition'] as $key => $val) {
                    $stmt->bindValue(":{$key}", $val);
                }
            }

            $stmt->execute();
            // Récupération du nombre d'éléments (la première colonne du premier résultat).
            $count = $stmt->fetchColumn();

            if ($count === false) {
                throw new RuntimeException("Erreur lors de la récupération du nombre d'éléments filtrés.");
            }
            return (int)$count;
        } catch (PDOException $e) {
            throw new DataBaseException(
                $this->fileLogger,
                "Erreur lors du comptage des véhicules filtrés dans la base de données.",
                [],
                0,
                $e
            );
        } catch (QueryBuilderException $e) {
            throw new DataBaseException(
                $this->fileLogger,
                "Erreur lors de la construction de la requête pour le comptage des véhicules filtrés.",
                [],
                0,
                $e
            );
        } catch (Exception $e) {
            throw new DataBaseException(
                $this->fileLogger,
                "Une erreur inattendue s'est produite lors du comptage des véhicules filtrés.",
                [],
                0,
                $e
            );
        }
    }
}

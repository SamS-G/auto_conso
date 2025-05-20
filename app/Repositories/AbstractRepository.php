<?php

namespace App\Repositories;

use App\Database\QueryBuilder;
use App\Exceptions\DataBaseException;
use App\Exceptions\QueryBuilderException;
use App\Repositories\Interfaces\RepositoryInterface;
use App\Repositories\Traits\CrudTrait;
use App\Services\FileLoggerService;
use App\Services\PdoService;
use PDO;
use PDOException;

abstract class AbstractRepository implements RepositoryInterface
{
    use CrudTrait;

    protected PDO $pdo;
    protected string $tableName;
    protected string $primaryKey = 'id';
    /**
     * Cache local pour éviter des requêtes répétées
     * Format : [table.column → [...enumValues]]
     */
    private static array $enumCache = [];
    protected QueryBuilder $queryBuilder;
    protected FileLoggerService $fileLoger;

    public function __construct(
        PdoService $pdoService,
        QueryBuilder $queryBuilder,
        FileLoggerService $fileLogerService
    ) {
        $this->pdo = $pdoService->getConnection();
        $this->queryBuilder = $queryBuilder;
        $this->fileLoger = $fileLogerService;
    }

    /**
     * @throws DataBaseException
     */
    protected function createEntitiesFromRows(array $rows): ?array
    {
        $entityClass = static::getEntityClassName();

        try {
            return $rows && $entityClass
                ? array_map(function ($row) use ($entityClass) {
                    return new $entityClass($row, $this->fileLoger);
                }, $rows)
                : null;
        } catch (\Throwable $e) {
            $errorMessage = "Erreur lors de l'hydratation de l'entité $entityClass : " . $e->getMessage();
            $this->fileLoger->error($errorMessage);
            throw new DataBaseException($this->fileLoger, $errorMessage, [], $e->getCode(), $e);
        }
    }

    /**
     * Récupère les données de chaque entité
     */
    abstract protected function getEntityData(object $entity): array;

    /**
     * Retourne le nom de la class de l'entité associée au Repository depuis le model enfant.
     */
    abstract protected static function getEntityClassName(): string;

    abstract protected static function getTableName(): string;

    /**
     * @throws DataBaseException
     */
    public function findOneBy(array $criteria): ?object
    {
        try {
            $results = $this->findEntities($criteria, [], 1);
            return $results[0] ?? null;
        } catch (DataBaseException | QueryBuilderException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw new DataBaseException(
                $this->fileLoger,
                "Erreur PDO lors de la recherche unique par critères : {$e->getMessage()}",
                $criteria,
                $e->getCode(),
                $e
            );
        } catch (\Exception $e) {
            throw new DataBaseException(
                $this->fileLoger,
                "Erreur inconnue lors de la recherche unique par critères : {$e->getMessage()}",
                $criteria,
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws DataBaseException
     */
    public function findById(int $id): ?array
    {
        $tableName = static::getTableName();

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$tableName} WHERE {$this->primaryKey} = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->createEntitiesFromRows($data);
        } catch (PDOException | DataBaseException $e) {
            throw new DataBaseException(
                $this->fileLoger,
                "Erreur PDO lors de la recherche par ID (ID: {$id}) : {$e->getMessage()}",
                ['id' => $id],
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param array|string[] $columns
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param array $joins
     * @param int|null $offset
     * @param int|null $limit
     * @return array
     * @throws QueryBuilderException
     * @throws DataBaseException
     */
    public function findAllPaginated(
        array $columns = ['*'],
        string $orderBy = null,
        string $orderDirection = 'ASC',
        array $joins = [],
        int $offset = null,
        int $limit = null
    ): array {

        try {
            if (!isset($limit) || !isset($offset)) {
                throw new QueryBuilderException(
                    $this->fileLoger,
                    "Offset ou limit manquants => finAllPaginated(), offset = {$offset}, limit = {$limit}",
                    [],
                    0
                );
            }

            $tableName = $this->getTableName();
            $selectedColumns = $this->queryBuilder->prepareColumns($tableName, $columns, $joins);
            $joinClause = $this->queryBuilder->buildJoinClauses($tableName, $joins);

            // Construction de la requête
            $query = $this->queryBuilder->buildPaginatedQuery(
                $tableName,
                $selectedColumns,
                $joinClause,
                null,
                $orderBy,
                $orderDirection
            );

            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->createEntitiesFromRows($rows);
        } catch (QueryBuilderException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw new DataBaseException(
                $this->fileLoger,
                "Erreur PDO lors de la récupération paginée (Offset: {$offset}, Limit: {$limit}) : {$e->getMessage()}",
                [
                    'columns' => $columns,
                    'orderBy' => $orderBy,
                    'orderDirection' => $orderDirection,
                    'joins' => $joins,
                    'offset' => $offset,
                    'limit' => $limit
                ],
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Compte le nombre total d'entrées d'une table
     * @throws DataBaseException
     */
    public function findTotalTableItems(string $tableName): int
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM $tableName");
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new DataBaseException(
                $this->fileLoger,
                "Erreur PDO lors du comptage des éléments de la table '{$tableName}' : {$e->getMessage()}",
                ['tableName' => $tableName],
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Recherche des entités par un ou plusieurs critères sans jointures
     *
     * @param array $criteria Un tableau associatif de critères (champ → valeur).
     * @param array $orderBy Un tableau associatif pour le tri (champ => 'ASC'|'DESC').
     * @param int|null $limit Limite du nombre de résultats.
     * @param int|null $offset Offset pour la pagination.
     * @return array Un tableau d'entités correspondant aux critères.
     * @throws DataBaseException
     */
    public function findEntities(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): array
    {
        $conditions = [];
        $values = [];
        foreach ($criteria as $field => $value) {
            $conditions[] = "{$field} = :{$field}";
            $values[":{$field}"] = $value;
        }

        $sql = "SELECT * FROM {$this->tableName}";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        if (!empty($orderBy)) {
            $orderByClauses = [];
            foreach ($orderBy as $field => $direction) {
                $orderByClauses[] = "{$field} " . strtoupper($direction);
            }
            $sql .= " ORDER BY " . implode(', ', $orderByClauses);
        }

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            if ($offset !== null) {
                $sql .= " OFFSET :offset";
            }
        }

        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($values as $param => $value) {
                $stmt->bindValue($param, $value);
            }

            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                if ($offset !== null) {
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                }
            }

            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->createEntitiesFromRows($rows);
        } catch (PDOException $e) {
            throw new DataBaseException(
                $this->fileLoger,
                "Erreur PDO lors de la recherche par critères : {$e->getMessage()}",
                ['criteria' => $criteria, 'orderBy' => $orderBy, 'limit' => $limit, 'offset' => $offset],
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Récupère les valeurs d'énumération à partir de tables.
     * Utilise un cache statique pour éviter les requêtes redondantes.
     *
     * @param array $tables Format attendu : ['nomTable' => 'colonnePourValeur' ou ['colonnePourValeur', 'colonneId']].
     * @return array Valeurs d'énumération organisées par table (tables => [nomTable => [id => valeur]]).
     * @throws DataBaseException En cas d'erreur lors de l'exécution des requêtes.
     */
    public function findEnumFromTables(array $tables): array
    {
        $result = [];

        try {
            foreach ($tables as $tableName => $config) {
                // Déterminer quelles colonnes utiliser pour la valeur et l'ID.
                $valueColumn = null;
                $idColumn = 'id';

                if (is_array($config)) {
                    $valueColumn = $config[0] ?? null;
                    $idColumn = $config[1] ?? 'id';
                } else {
                    $valueColumn = $config;
                }

                // Ignorer si aucune colonne de valeur n'est spécifiée.
                if (!$valueColumn) {
                    continue;
                }

                $cacheKey = "table:{$tableName}.{$valueColumn}";

                // Utiliser les données du cache si disponibles.
                if (isset(self::$enumCache[$cacheKey])) {
                    $result[$tableName] = self::$enumCache[$cacheKey];
                    continue;
                }

                // Récupérer les données de la table.
                $sql = "SELECT `{$idColumn}`, `{$valueColumn}` FROM `{$tableName}` ORDER BY `{$valueColumn}`";
                $stmt = $this->pdo->query($sql);

                if (!$stmt) {
                    $result[$tableName] = [];
                    continue;
                }

                // Construire le tableau [id → valeur].
                $values = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $values[$row[$idColumn]] = $row[$valueColumn];
                }

                // Stocker dans le cache et dans le résultat.
                self::$enumCache[$cacheKey] = $values;
                $result[$tableName] = $values;
            }
        } catch (PDOException $e) {
            throw new DataBaseException(
                $this->fileLoger,
                "Erreur lors de la récupération des énumérations depuis les tables : {$e->getMessage()}",
                ['tables' => $tables],
                $e->getCode(),
                $e
            );
        }

        return $result;
    }

    /**
     * Récupère les valeurs d'énumération à partir de colonnes de type ENUM.
     * Utilise un cache statique pour éviter les requêtes redondantes.
     *
     * @param array $columns Format attendu : ['nomTable' → ['colonne1', 'colonne2']].
     * @return array Valeurs d'énumération organisées par table et colonne
     * (columns → [nomTable → [colonne → [valeur → valeur]]]).
     * @throws DataBaseException En cas d'erreur lors de l'exécution des requêtes.
     */
    public function findEnumFromColumns(array $columns): array
    {
        $result = [];

        try {
            foreach ($columns as $tableName => $columnNames) {
                // S'assurer que les noms de colonnes sont dans un tableau.
                $columnNames = (array)$columnNames;

                foreach ($columnNames as $columnName) {
                    $cacheKey = "column:{$tableName}.{$columnName}";

                    // Utiliser les données du cache si disponibles.
                    if (isset(self::$enumCache[$cacheKey])) {
                        $result['columns'][$tableName][$columnName] = self::$enumCache[$cacheKey];
                        continue;
                    }

                    // Requête pour obtenir le type de la colonne.
                    $sql = "SELECT COLUMN_TYPE FROM information_schema.columns
                       WHERE table_schema = DATABASE()
                       AND table_name = :table
                       AND column_name = :column";

                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        'table' => $tableName,
                        'column' => $columnName
                    ]);

                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$row) {
                        $result[$tableName][$columnName] = [];
                        continue;
                    }

                    // Extraire les valeurs de l'ENUM si le type est compatible.
                    $columnType = $row['COLUMN_TYPE'];

                    if (strpos($columnType, 'enum') === 0) {
                        // Extraction des valeurs entre parenthèses: enum('val1','val2') -> 'val1','val2'.
                        preg_match('/^enum\((.*)\)$/', $columnType, $matches);

                        if (isset($matches[1])) {
                            // Convertir la chaîne CSV en tableau.
                            $values = str_getcsv($matches[1], ',', "'");

                            // Créer un tableau associatif [valeur => valeur].
                            $formattedValues = array_combine($values, $values);

                            // Stocker dans le cache et dans le résultat.
                            self::$enumCache[$cacheKey] = $formattedValues;
                            $result[$tableName][$columnName] = $formattedValues;
                            continue;
                        }
                    }

                    $result[$tableName][$columnName] = [];
                }
            }
        } catch (PDOException $e) {
            throw new DataBaseException(
                $this->fileLoger,
                "Erreur lors de la récupération des énumérations depuis les colonnes : {$e->getMessage()}",
                ['columns' => $columns],
                $e->getCode(),
                $e
            );
        }

        return $result;
    }

    /**
     * Supprime de base une entrée
     * @param int $id
     * @return bool
     * @throws DataBaseException
     */
    public function delete(int $id): bool
    {
        try {
            $tableName = $this->getTableName();
            $stmt = $this->pdo->prepare("DELETE FROM {$tableName} WHERE {$this->primaryKey} = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            throw new DataBaseException(
                $this->fileLoger,
                "Erreur PDO lors de la suppression (ID: {$id}) : {$e->getMessage()}",
                ['id' => $id],
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Traite les données brutes de la base de données et les convertit en entités avec propriété formatée
     *
     * Cette méthode transforme les lignes de données en entités et convertit un code/identifiant
     * en valeur lisible pour l'entité spécifiée ou pour toutes les entités selon le paramètre.
     *
     * @param array $rows Données brutes issues de la requête SQL
     * @param string $propertyName Nom de la propriété à convertir (ex : 'energyType', 'status', etc.)
     * @param string $getterMethod Nom de la méthode getter pour obtenir la valeur (ex : 'getEnergyType')
     * @param string $conversionMethod Nom de la méthode pour convertir le code en nom (ex : 'getEnergyTypeNameByCode')
     * @param string $setterMethod Nom de la méthode setter pour définir la nouvelle valeur (ex : 'setEnergyType')
     * @param bool $firstOnly Si true, convertit uniquement pour la première entité, sinon pour toutes
     * @return array|null Tableau d'entités avec la propriété formatée ou null si aucune donnée
     * @throws DataBaseException
     */
    protected function processEntityDetails(
        array $rows,
        string $propertyName,
        string $getterMethod,
        string $conversionMethod,
        string $setterMethod,
        bool $firstOnly = true
    ): ?array {
        if (empty($rows)) {
            return null;
        }

        // Crée les entités à partir des données brutes
        $entities = $this->createEntitiesFromRows($rows);

        // Vérifie qu'au moins une entité a été créée
        if (!empty($entities)) {
            // Détermine quelles entités traiter
            $entitiesToProcess = $firstOnly ? array_slice($entities, 0, 1) : $entities;

            foreach ($entitiesToProcess as $entity) {
                if (
                    method_exists($entity, $getterMethod) &&
                    method_exists($entity, $conversionMethod) &&
                    method_exists($entity, $setterMethod)
                ) {
                    // Obtient la valeur actuelle (code)
                    $codeValue = $entity->$getterMethod();

                    // Convertit le code en nom lisible
                    $readableName = $entity->$conversionMethod($codeValue);

                    // Définit la nouvelle valeur
                    $entity->$setterMethod($readableName);
                }
            }
        }

        return $entities;
    }
}

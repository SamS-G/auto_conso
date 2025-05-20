<?php

namespace App\Database;

use App\Exceptions\QueryBuilderException;
use App\Services\FileLoggerService;

/**
 * Classe QueryBuilder
 *
 * Cette classe permet de construire dynamiquement des requêtes SQL
 * avec support pour les sélections, jointures, conditions et pagination.
 *
 * @package App\Database
 */
class QueryBuilder
{
    /**
     * Service de journalisation des fichiers
     *
     * @var FileLoggerService
     */
    private FileLoggerService $fileLoggerService;

    /**
     * Constructeur
     *
     * @param FileLoggerService $fileLoggerService Service de journalisation pour les erreurs
     */
    public function __construct(FileLoggerService $fileLoggerService)
    {
        $this->fileLoggerService = $fileLoggerService;
    }

    /**
     * Construit une requête SELECT.
     *
     * Cette méthode génère une requête SQL SELECT complète avec les options
     * de jointure, filtrage et tri.
     *
     * @param string $tableName Nom de la table principale
     * @param array $selectedColumns Colonnes à sélectionner
     * @param string|null $joinClause Clause JOIN préformatée (optionnel)
     * @param string|null $whereClause Clause WHERE préformatée (optionnel)
     * @param string|null $orderBy Colonne de tri (optionnel)
     * @param string $orderDirection Direction du tri ('ASC' ou 'DESC')
     * @return string Requête SQL complète
     * @throws QueryBuilderException Si les paramètres sont invalides
     */
    public function buildQuery(
        string $tableName,
        array $selectedColumns,
        ?string $joinClause = null,
        ?string $whereClause = null,
        ?string $orderBy = null,
        string $orderDirection = 'ASC'
    ): string {
        // Validation des paramètres obligatoires
        if (empty($tableName)) {
            throw new QueryBuilderException($this->fileLoggerService, "Le nom de la table ne peut pas être vide.");
        }
        if (empty($selectedColumns)) {
            throw new QueryBuilderException(
                $this->fileLoggerService,
                "Vous devez sélectionner au moins une colonne."
            );
        }
        if (!in_array(strtoupper($orderDirection), ['ASC', 'DESC'])) {
            throw new QueryBuilderException($this->fileLoggerService, "La direction de tri doit être 'ASC' ou 'DESC'.");
        }

        // Construction de la requête
        $columnsStr = implode(', ', $selectedColumns);
        $joinClause = $joinClause ? " $joinClause" : '';
        $whereClause = $whereClause ?? '';
        $orderByClause = $orderBy ? $this->buildOrderByClause($tableName, $orderBy, $orderDirection) : '';

        // Assemblage de la requête finale
        return "SELECT $columnsStr FROM {$tableName}{$joinClause}{$whereClause}{$orderByClause}";
    }

    /**
     * Construit une requête paginée avec jointures et tri.
     *
     * Similaire à buildQuery() mais ajoute les clauses LIMIT et OFFSET
     * pour la pagination des résultats.
     *
     * @param string $tableName Nom de la table principale
     * @param array $selectedColumns Colonnes à sélectionner
     * @param string|null $joinClause Clause JOIN préformatée (optionnel)
     * @param string|null $whereClause Clause WHERE préformatée (optionnel)
     * @param string|null $orderBy Colonne de tri (optionnel)
     * @param string $orderDirection Direction du tri ('ASC' ou 'DESC')
     * @return string Requête SQL paginée
     * @throws QueryBuilderException Si les paramètres sont invalides
     */
    public function buildPaginatedQuery(
        string $tableName,
        array $selectedColumns,
        ?string $joinClause = null,
        ?string $whereClause = null,
        ?string $orderBy = null,
        string $orderDirection = 'ASC'
    ): string {
        if (!empty($tableName)) {
            // Validation des paramètres obligatoires
            if (empty($selectedColumns)) {
                throw new QueryBuilderException(
                    $this->fileLoggerService,
                    "Vous devez sélectionner au moins une colonne."
                );
            }
            if (!in_array(strtoupper($orderDirection), ['ASC', 'DESC'])) {
                throw new QueryBuilderException(
                    $this->fileLoggerService,
                    "La direction de tri doit être 'ASC' ou 'DESC'."
                );
            }

            // Construction de la requête
            $columnsStr = implode(', ', $selectedColumns);
            $joinClause = $joinClause ? " $joinClause" : '';
            $whereClause = $whereClause ? "  $whereClause" : '';
            $orderByClause = $this->buildOrderByClause($tableName, $orderBy, $orderDirection);

            // Requête avec pagination (les paramètres :limit et :offset seront liés plus tard)
            return "SELECT $columnsStr FROM {$tableName} {$joinClause} {$whereClause} {$orderByClause} LIMIT :limit OFFSET :offset";
        } else {
            throw new QueryBuilderException($this->fileLoggerService, "Le nom de la table ne peut pas être vide.");
        }
    }

    /**
     * Prépare les colonnes à sélectionner, incluant les colonnes jointes.
     *
     * Cette méthode formate les noms des colonnes avec leurs tables respectives
     * et gère les colonnes des tables jointes.
     *
     * @param string $tableName Nom de la table principale
     * @param array $columns Tableau des colonnes de la table principale
     * @param array $joins Définition des jointures et colonnes associées
     * @return array Tableau des colonnes formatées pour la requête SQL
     * @throws QueryBuilderException Si le format des paramètres est incorrect
     */
    public function prepareColumns(string $tableName, array $columns, array $joins = []): array
    {
        // Validation des paramètres obligatoires
        if (empty($tableName)) {
            throw new QueryBuilderException($this->fileLoggerService, "Le nom de la table ne peut pas être vide.");
        }
        if (empty($columns) && empty($joins)) {
            throw new QueryBuilderException(
                $this->fileLoggerService,
                "Vous devez spécifier au moins une colonne à sélectionner ou une jointure."
            );
        }

        $selectedColumns = [];

        // Cas particulier : sélection de toutes les colonnes SANS jointure
        if ($columns === ['*'] && empty($joins)) {
            return ["{$tableName}.*"];
        }

        // Traitement des colonnes de la table principale
        foreach ($columns as $column) {
            if (empty($column) || !is_string($column)) {
                throw new QueryBuilderException(
                    $this->fileLoggerService,
                    "Le nom d'une colonne à sélectionner ne peut pas être vide et doit être une chaîne de caractères."
                );
            }
            $selectedColumns[] = "{$tableName}.{$column}";
        }

        // Traitement des colonnes des tables jointes
        if (!empty($joins)) {
            if (!is_array($joins)) {
                throw new QueryBuilderException(
                    $this->fileLoggerService,
                    "Le format des jointures est incorrect. Attendu un tableau associatif."
                );
            }

            // Pour chaque jointure définie
            foreach ($joins as $join) {
                if (!is_array($join) || !isset($join['table']) || !isset($join['display'])) {
                    throw new QueryBuilderException(
                        $this->fileLoggerService,
                        "Le format d'une jointure est incorrect. Attendu ['table' => '...', 'display' => [...]]."
                    );
                }
                if (!is_array($join['display'])) {
                    throw new QueryBuilderException(
                        $this->fileLoggerService,
                        "La clé 'display' dans une jointure doit être un tableau de noms de colonnes."
                    );
                }

                // Pour chaque colonne à afficher de cette jointure
                foreach ($join['display'] as $item) {
                    $joinData = [
                        'table' => $join['table'],
                        'display' => $item
                    ];
                    $selectedColumns[] = $this->formatJoinedColumn($joinData);
                }
            }
        }

        return $selectedColumns;
    }

    /**
     * Formate une colonne jointe pour l'inclure dans la clause SELECT.
     *
     * Crée une expression pour sélectionner une colonne d'une table jointe
     * avec un alias approprié.
     *
     * @param array $join Données de la jointure contenant 'table' et 'display'
     * @return string Expression SQL formatée (ex : "table.colonne AS colonne")
     * @throws QueryBuilderException Si le format des données est incorrect
     */
    private function formatJoinedColumn(array $join): string
    {
        // Validation de la structure des données
        if (!isset($join['table']) || !isset($join['display'])) {
            throw new QueryBuilderException(
                $this->fileLoggerService,
                "Le format des données de la colonne jointe est incorrect."
            );
        }

        // Validation du nom de la table
        if (empty($join['table']) || !is_string($join['table'])) {
            throw new QueryBuilderException(
                $this->fileLoggerService,
                "Le nom de la table jointe ne peut pas être vide et doit être une chaîne de caractères."
            );
        }

        // Validation du nom de la colonne
        if (empty($join['display']) || !is_string($join['display'])) {
            throw new QueryBuilderException(
                $this->fileLoggerService,
                "Le nom de la colonne jointe à afficher ne peut pas être vide et doit être une chaîne de caractères."
            );
        }

        // Formatage de l'expression avec alias
        return sprintf(
            '%s.%s AS %s',
            $join['table'],
            $join['display'],
            $join['display']
        );
    }

    /**
     * Construit la clause ORDER BY.
     *
     * Génère une clause SQL pour trier les résultats par une colonne spécifiée.
     *
     * @param string $tableName Nom de la table contenant la colonne de tri
     * @param string|null $orderBy Nom de la colonne pour le tri
     * @param string $orderDirection Direction du tri ('ASC' ou 'DESC')
     * @return string Clause ORDER BY complète
     * @throws QueryBuilderException Si les paramètres sont invalides
     */
    public function buildOrderByClause(string $tableName, ?string $orderBy, string $orderDirection = 'ASC'): string
    {
        if ($orderBy) {
            // Validation des paramètres
            if (empty($tableName)) {
                throw new QueryBuilderException(
                    $this->fileLoggerService,
                    "Le nom de la table ne peut pas être vide et doit être une chaîne de caractères
                     pour la clause ORDER BY."
                );
            }
            if (empty($orderBy) || !is_string($orderBy)) {
                throw new QueryBuilderException(
                    $this->fileLoggerService,
                    "Le nom de la colonne pour le tri ne peut pas être vide et doit être une chaîne de caractères."
                );
            }
            if (!in_array(strtoupper($orderDirection), ['ASC', 'DESC'])) {
                throw new QueryBuilderException(
                    $this->fileLoggerService,
                    "La direction de tri doit être 'ASC' ou 'DESC'."
                );
            }

            // Construction de la clause ORDER BY
            return "ORDER BY {$tableName}.{$orderBy} " . strtoupper($orderDirection);
        }

        // Retourne une chaîne vide si aucun tri n'est demandé
        return '';
    }

    /**
     * Construit la clause WHERE.
     *
     * Génère une clause WHERE à partir d'un tableau de conditions structurées.
     * Utilise des paramètres nommés pour la préparation sécurisée des requêtes.
     *
     * @param array $conditions Tableau de conditions [['table' => '...', 'condition' => ['colonne' => 'valeur']]]
     * @return string Clause WHERE formatée
     * @throws QueryBuilderException Si le format des conditions est incorrect
     */
    public function buildWhereClause(array $conditions): string
    {
        // Si aucune condition n'est spécifiée, retourne une chaîne vide
        if (empty($conditions)) {
            return '';
        }

        $whereParts = [];

        // Traitement de chaque condition
        foreach ($conditions as $values) {
            // Validation du format de la condition
            if (
                !is_array($values)
                || !isset($values['table'])
                || !isset($values['condition'])
                || !is_array($values['condition'])
            ) {
                throw new QueryBuilderException(
                    $this->fileLoggerService,
                    "Le format d'une condition WHERE est incorrect.
                     Attendu ['table' => '...', 'condition' => ['colonne' => 'valeur']]."
                );
            }

            // Validation du nom de la table
            if (empty($values['table']) || !is_string($values['table'])) {
                throw new QueryBuilderException(
                    $this->fileLoggerService,
                    "Le nom de la table dans une condition WHERE ne peut pas être vide
                     et doit être une chaîne de caractères."
                );
            }

            // Construction des parties de la clause WHERE avec paramètres nommés
            foreach ($values['condition'] as $key => $value) {
                if (empty($key) || !is_string($key)) {
                    throw new QueryBuilderException(
                        $this->fileLoggerService,
                        "Le nom de la colonne dans une condition WHERE ne peut pas être vide
                         et doit être une chaîne de caractères."
                    );
                }
                $whereParts[] = "{$values['table']}." . "{$key}" . "= :{$key}";
            }
        }

        // Construction finale de la clause WHERE
        return " WHERE " . implode(' AND ', $whereParts);
    }

    /**
     * Construit les clauses de jointure SQL.
     *
     * Génère des clauses JOIN pour relier la table principale aux tables secondaires
     * en utilisant les clés étrangères spécifiées.
     *
     * @param string $tableName Nom de la table principale
     * @param array $joins Définition des jointures [colonne_id → ['table' → 'nom_table', 'display' → [...]]]
     * @param string $jointDirection Type de jointure ('LEFT', 'RIGHT', 'INNER')
     * @return string Clauses JOIN complètes
     * @throws QueryBuilderException Si les paramètres sont invalides
     */
    public function buildJoinClauses(string $tableName, array $joins, string $jointDirection = 'LEFT'): string
    {
        // Validation des paramètres de base
        if (empty($tableName)) {
            throw new QueryBuilderException(
                $this->fileLoggerService,
                "Le nom de la table principale ne peut pas être vide 
                et doit être une chaîne de caractères pour la clause JOIN."
            );
        }
        if (!in_array(strtoupper($jointDirection), ['LEFT', 'RIGHT', 'INNER'])) {
            throw new QueryBuilderException(
                $this->fileLoggerService,
                "La direction de la jointure doit être 'LEFT', 'RIGHT' ou 'INNER'."
            );
        }

        $joinClause = '';

        // Construction de chaque jointure
        foreach ($joins as $localColumn => $join) {
            // Validation de la colonne locale
            if (empty($localColumn) || !is_string($localColumn)) {
                throw new QueryBuilderException(
                    $this->fileLoggerService,
                    "Le nom de la colonne locale pour la jointure ne peut pas être vide
                     et doit être une chaîne de caractères."
                );
            }

            // Validation de la structure de la jointure
            if (!is_array($join) || !isset($join['table']) || !is_string($join['table']) || empty($join['table'])) {
                throw new QueryBuilderException(
                    $this->fileLoggerService,
                    "Le format d'une jointure est incorrect. Attendu ['table' => 'nom_table', 'display' => [...]]."
                );
            }

            // Construction de la clause JOIN (suppose que la clé primaire de la table jointe est 'id')
            $joinClause .= " " . strtoupper($jointDirection) . " JOIN {$join['table']} ON {$tableName}.$localColumn = {$join['table']}.id";
        }

        return $joinClause;
    }
}

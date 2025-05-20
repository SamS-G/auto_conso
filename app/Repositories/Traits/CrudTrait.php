<?php

namespace App\Repositories\Traits;

use App\Exceptions\DataBaseException;
use PDO;
use PDOException;
use PDOStatement;

trait CrudTrait
{
    protected PDO $pdo;

    /**
     * Insère des données dans la base de données.
     *
     * @param array $data Les données à insérer (clé → valeur).
     * @return PDOStatement Le PDOStatement après l'exécution réussie.
     * @throws DatabaseException En cas d'erreur lors de l'insertion.
     */
    protected function insert(array $data): PDOStatement
    {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->tableName} ({$fields}) VALUES ({$placeholders})";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $stmt;
        } catch (PDOException $e) {
            throw new DatabaseException(
                $this->fileLogger,
                "Erreur lors de l'insertion dans la table '{$this->tableName}': " . $e->getMessage(),
                [],
                500,
                $e
            );
        }
    }

    /**
     * Met à jour une ligne dans la base de données en fonction de son ID.
     *
     * @param int $id L'ID de la ligne à mettre à jour.
     * @param array $data Les nouvelles données (clé => valeur).
     * @return PDOStatement Le PDOStatement après l'exécution réussie.
     * @throws DatabaseException En cas d'erreur lors de la mise à jour.
     */
    protected function update(int $id, array $data): PDOStatement
    {
        $setClauses = [];
        foreach (array_keys($data) as $field) {
            $setClauses[] = "{$field} = :{$field}";
        }
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setClauses) . " WHERE {$this->primaryKey} = :{$this->primaryKey}";
        try {
            $stmt = $this->pdo->prepare($sql);
            $data[$this->primaryKey] = $id;
            $stmt->execute($data);
            return $stmt;
        } catch (PDOException $e) {
            throw new DatabaseException(
                $this->fileLogger,
                "Erreur lors de la mise à jour de l'ID '{$id}' dans la table '{$this->tableName}': " . $e->getMessage(),
                [],
                500,
                $e
            );
        }
    }
}

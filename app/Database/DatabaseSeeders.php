<?php

namespace App\Database;

use App\Services\ConfigService;
use App\Services\Interfaces\LoggerInterface;
use PDO;
use PDOException;

class DatabaseSeeders
{
    /**
     * Instance de la connexion PDO à la base de données.
     *
     * @var PDO
     */
    protected PDO $pdo;

    /**
     * Instance du logger pour enregistrer les événements et erreurs.
     *
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Chemin vers le répertoire contenant les fichiers SQL de seeding.
     *
     * @var string
     */
    private string $seedDataPath;

    /**
     * Constructeur de la classe DatabaseSeeders.
     * Initialise la connexion PDO, le logger et récupère le chemin des données de seeding depuis la configuration.
     *
     * @param PDO $pdo Instance de la connexion PDO à la base de données.
     * @param LoggerInterface $logger Instance du logger.
     * @param ConfigService $configService Instance du service de configuration.
     */
    public function __construct(PDO $pdo, LoggerInterface $logger, ConfigService $configService)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;

        // Récupère le chemin vers le répertoire des données de seeding depuis la configuration
        // Si la clé n'existe pas, une chaîne vide est utilisée par défaut.
        $this->seedDataPath = $configService->get('database.seed_data_path', '');
    }

    /**
     * Méthode principale pour lancer la procédure de seeding de la base de données.
     * Elle appelle les méthodes pour seeder les tables d'énumération et les autres tables.
     *
     * @return void
     */
    public function run()
    {
        // Logue le début de la procédure de seeding.
        $this->logger->info("Début de la procédure de seeding de la base de données...\n");

        // Seed les tables d'énumération (celles qui contiennent des valeurs prédéfinies comme
        // les types de boîte de vitesses ou les marques).
        $this->seedEnumTables();

        // Seed les autres tables qui contiennent des données plus complexes et potentiellement
        // des relations (clés étrangères).
        $this->seedOtherTables();

        // Logue la fin de la procédure de seeding.
        $this->logger->info("Procédure de seeding de la base de données terminée.\n");
    }

    /**
     * Méthode privée pour seeder les tables d'énumération.
     * Elle lit les fichiers SQL correspondants et exécute les requêtes.
     *
     * @return void
     */
    private function seedEnumTables()
    {
        // Logue le début du seeding des tables d'énumération.
        $this->logger->info("Seeding des tables d'énumération...\n");

        // Liste des noms des tables d'énumération à seeder.
        $enumTables = ['gearbox_type', 'brand'];

        // Parcourt la liste des tables d'énumération.
        foreach ($enumTables as $tableName) {
            // Construit le chemin complet vers le fichier SQL pour la table actuelle.
            $sqlFilePath = sprintf('%s%s%s', $this->seedDataPath, $tableName, '.sql');

            // Vérifie si le fichier SQL existe.
            if (file_exists($sqlFilePath)) {
                // Lit le contenu du fichier SQL.
                $sql = file_get_contents($sqlFilePath);

                // Sépare les différentes requêtes SQL dans le fichier en utilisant ";\n" comme délimiteur.
                $queries = explode(";\n", $sql);

                // Parcourt chaque requête SQL.
                foreach ($queries as $query) {
                    // Supprime les espaces blancs au début et à la fin de la requête.
                    $query = trim($query);
                    // Vérifie si la requête n'est pas vide.
                    if (!empty($query)) {
                        // Logue l'exécution du script SQL pour la table actuelle.
                        $this->logger->info("Exécution du script SQL pour la table '$tableName'...\n");
                        // Exécute la requête SQL.
                        $this->executeSqlFile($query);
                    }
                }
            } else {
                // Si le fichier SQL n'est pas trouvé, logue une erreur fatale.
                $this->logger->fatal("Fichier SQL non trouvé pour la table '$tableName' : '$sqlFilePath'\n");
            }
        }
        // Logue la fin du seeding des tables d'énumération.
        $this->logger->info("Seeding des tables d'énumération terminé.\n");
    }

    /**
     * Méthode privée pour seeder les autres tables (celles avec des données plus complexes).
     * Elle suit une logique similaire à `seedEnumTables` mais peut avoir un ordre spécifique pour gérer
     * les clés étrangères.
     *
     * @return void
     */
    private function seedOtherTables()
    {
        // Logue le début du seeding des autres tables.
        $this->logger->info("Seeding des autres tables...\n");

        // Liste des noms des autres tables à seeder, avec un ordre d'insertion potentiellement important
        // à cause des clés étrangères.
        $otherTables = ['car_model', 'consumption_data'];

        // Parcourt la liste des autres tables.
        foreach ($otherTables as $tableName) {
            // Construit le chemin complet vers le fichier SQL pour la table actuelle.
            $sqlFilePath = sprintf('%s%s%s', $this->seedDataPath, $tableName, '.sql');

            // Vérifie si le fichier SQL existe.
            if (file_exists($sqlFilePath)) {
                // Lit le contenu du fichier SQL.
                $sql = file_get_contents($sqlFilePath);

                // Sépare les différentes requêtes SQL dans le fichier.
                $queries = explode(";\n", $sql);

                // Parcourt chaque requête SQL.
                foreach ($queries as $query) {
                    // Supprime les espaces blancs au début et à la fin de la requête.
                    $query = trim($query);
                    // Vérifie si la requête n'est pas vide.
                    if (!empty($query)) {
                        // Logue l'exécution du script SQL pour la table actuelle.
                        $this->logger->info("Exécution du script SQL pour la table '$tableName'...\n");
                        // Exécute la requête SQL.
                        $this->executeSqlFile($query);
                    }
                }
            } else {
                // Si le fichier SQL n'est pas trouvé, logue une information
                $this->logger->fatal("Fichier SQL non trouvé pour la table '$tableName' : '$sqlFilePath'\n");
            }
        }
        // Logue la fin du seeding des autres tables.
        $this->logger->info("Seeding des autres tables terminé.\n");
    }

    /**
     * Méthode privée pour exécuter une requête SQL à partir d'un fichier.
     * Gère les exceptions PDO lors de l'exécution.
     *
     * @param string $sql La requête SQL à exécuter.
     * @return void
     */
    private function executeSqlFile(string $sql)
    {
        try {
            // Prépare la requête SQL pour l'exécution.
            $statement = $this->pdo->prepare($sql);
            // Exécute la requête préparée.
            $statement->execute();
            // Logue le succès de l'exécution du script.
            $this->logger->info("Script exécuté avec succès.\n");
        } catch (PDOException $e) {
            // En cas d'erreur PDO lors de l'exécution, logue une erreur fatale avec le message d'erreur.
            $this->logger->fatal("Erreur lors de l'exécution du script : " . $e->getMessage() . "\n");
        }
    }
}

<?php

namespace App\Services;

use App\Exceptions\DataBaseException;
use PDO;
use PDOException;

/**
 * Gère la connection à la base de données
 */
class PdoService
{
    private FileLoggerService $loggerService;
    private ConfigService $configService;
    /**
     * @var array|mixed|null
     */
    private $host;
    /**
     * @var array|mixed|null
     */
    private $database;
    /**
     * @var array|mixed|null
     */
    private $charset;
    /**
     * @var array|mixed|null
     */
    private $username;
    /**
     * @var array|mixed|null
     */
    private $password;
    private PDO $connection;

    /**
     * @throws DataBaseException
     */
    public function __construct(ConfigService $configService, FileLoggerService $loggerService)
    {
        $this->configService = $configService;
        $this->host = $this->configService->get('database.host', []);
        $this->database = $this->configService->get('database.databaseName', []);
        $this->charset = $this->configService->get('database.charset', 'utf8mb4');
        $this->username = $this->configService->get('database.username', 'root');
        $this->password = $this->configService->get('database.password', '');
        $this->loggerService = $loggerService;
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            $this->host,
            $this->database,
            $this->charset
        );

        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // lance une exception en cas d'erreur
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // récupère chaque ligne sous forme de tableau indexé
                PDO::ATTR_EMULATE_PREPARES => false // utilisation des requêtes préparées

            ]);
        } catch (PDOException $exception) {
            throw new DatabaseException(
                $this->loggerService,
                "Erreur lors de la connexion à la base de données : {$exception->getMessage()}",
                [
                'fichier' => $exception->getFile(),
                'ligne' => $exception->getLine(),
                'stackTraces' => $exception->getTraceAsString()
                ],
                $exception->getCode(),
                $exception
            );
        }
    }
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}

<?php

namespace App\Http\Controllers;

use App\Database\DatabaseSeeders;
use App\Services\Interfaces\LoggerInterface;

class SeedController
{
    protected DatabaseSeeders $databaseSeeder;
    protected LoggerInterface $logger;

    public function __construct(DatabaseSeeders $databaseSeeder, LoggerInterface $logger)
    {
        $this->databaseSeeder = $databaseSeeder;
        $this->logger = $logger;
    }

    public function seed()
    {
        $this->logger->info("Requête reçue pour exécuter le seeder via /seed.");
        $this->databaseSeeder->run();
        $this->logger->info("Seeding terminé. Affichage de la sortie.");
        echo 'Opération réussie !';
    }
}

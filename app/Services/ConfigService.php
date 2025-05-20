<?php

namespace App\Services;

class ConfigService
{
    protected array $config = [];
    protected string $configPath; // Répertoire des fichiers de config

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        $this->loadConfig();
    }

    /**
     * Charge les fichiers de config
     * @return void
     */
    protected function loadConfig(): void
    {
        $appConfigPath = $this->configPath . '/app.php';
        $databaseConfigPath = $this->configPath . '/database.php';
        $routesConfigPath = $this->configPath . '/routes.php';

        if (file_exists($appConfigPath)) {
            $this->config['app'] = include $appConfigPath;
        }
        if (file_exists($databaseConfigPath)) {
            $this->config['database'] = include $databaseConfigPath;
        }
        if (file_exists($routesConfigPath)) {
            $this->config['routes'] = include $routesConfigPath;
        }
    }

    /**
     * Permet d'accéder à des sections spécifiques de la config (ex: 'database.host')
     * et une valeur par défaut peux être définie si la clef n'est pas trouvé
     * @return array|mixed|null
     */
    public function get(string $key, $default = null)
    {
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = $this->config;

            foreach ($keys as $segment) {
                if (isset($value[$segment])) {
                    $value = $value[$segment];
                } else {
                    return $default;
                }
            }
            return $value;
        }
        return $this->config[$key] ?? $default;
    }

    /**
     * Récupère le tableau complet de config
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}

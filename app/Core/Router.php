<?php

namespace App\Core;

use App\Services\ConfigService;

class Router
{
    /**
     * Instance du service de configuration pour accéder aux paramètres de l'application.
     *
     * @var ConfigService
     */
    protected ConfigService $configService;

    /**
     * Namespace racine des contrôleurs de l'application.
     * Ce namespace est utilisé pour construire le nom complet de la classe du contrôleur.
     *
     * @var string
     */
    private string $controllersNamespace;

    /**
     * Tableau associatif contenant les routes de l'application.
     * La structure typique est : ['METHOD' => ['/path' => 'Controller@method']].
     * Ces routes sont chargées depuis la configuration.
     *
     * @var array
     */
    private array $routes;

    /**
     * Instance de l'injecteur de dépendances pour instancier les contrôleurs
     * et résoudre leurs dépendances.
     *
     * @var DependencyInjector
     */
    private DependencyInjector $dependencyInjector;

    /**
     * Constructeur de la classe Router.
     * Il reçoit le service de configuration et l'injecteur de dépendances en arguments.
     * Charge les routes depuis la configuration et récupère le namespace des contrôleurs.
     *
     * @param ConfigService $configService Instance du service de configuration.
     * @param DependencyInjector $dependencyInjector Instance de l'injecteur de dépendances.
     */
    public function __construct(ConfigService $configService, DependencyInjector $dependencyInjector)
    {
        $this->configService = $configService;
        // Charge les routes depuis le fichier de configuration ('routes'), tableau vide par défaut si non trouvé.
        $this->routes = $this->configService->get('routes', []);
        // Récupère le namespace des contrôleurs depuis la configuration ('app.controllers'), chaîne vide par défaut.
        $this->controllersNamespace = $this->configService->get('app.controllers', '');
        $this->dependencyInjector = $dependencyInjector;
    }

    /**
     * Méthode principale pour dispatcher une requête HTTP entrante.
     * Elle analyse l'URI et la méthode HTTP, recherche une route correspondante
     * et exécute l'action associée.
     *
     * @param string $uri L'URI de la requête (par exemple, '/vehicules?page=1').
     * @param string $method La méthode HTTP de la requête (par exemple, 'GET', 'POST').
     * @return void
     */
    public function dispatch(string $uri, string $method)
    {
        // Extrait le chemin de l'URI en supprimant '/index.php' et en ne conservant que la partie du chemin.
        $path = preg_replace('/\/index\.php/', '', parse_url($uri, PHP_URL_PATH));

        // Si le chemin est la racine ('/' ou ''), on redirige l'utilisateur vers la page des véhicules (page 1).
        if ($path === '/' || $path === '') {
            header('Location: /vehicules?page=1');
            exit(); // Termine l'exécution du script après la redirection.
        }

        // Vérifie si une route correspondante existe pour la méthode HTTP et le chemin actuels.
        if (isset($this->routes[$method][$path])) {
            $action = $this->routes[$method][$path]; // Récupère la chaîne de l'action (format 'Controller@method').
            $this->executeAction($action); // Exécute l'action associée à la route.
            return; // Termine l'exécution du script après l'exécution de l'action.
        }

        // Si aucune route correspondante n'est trouvée, affiche une erreur 404.
        $this->error404();
    }

    /**
     * Méthode privée pour exécuter une action de contrôleur spécifiée.
     * Elle prend une chaîne au format 'Controller@method', instancie le contrôleur
     * en utilisant l'injecteur de dépendances et appelle la méthode spécifiée.
     *
     * @param string $action La chaîne représentant l'action du contrôleur (format 'Controller@method').
     * @return void
     */
    private function executeAction(string $action)
    {
        //Initialise un tableau vide pour les paramètres à passer à la méthode du contrôleur (actuellement vide).
        $params = [];
        // Sépare le nom du contrôleur et le nom de la méthode en utilisant '@' comme délimiteur
        list($controllerName, $method) = explode('@', $action); // Directement dans des variables
        // Construit le nom complet de la classe du contrôleur en utilisant le namespace et le nom du contrôleur.
        $controllerClass = sprintf("%s\%s", $this->controllersNamespace, $controllerName);

        // Vérifie si la classe du contrôleur existe.
        if (class_exists($controllerClass)) {
            try {
                // Tente de résoudre (instancier) le contrôleur en utilisant l'injecteur de dépendances.
                // L'injecteur se chargera de créer l'instance du contrôleur et d'injecter ses dépendances.
                $controllerInstance = $this->dependencyInjector->resolve($controllerClass);

                // Vérifie si la méthode spécifiée existe dans l'instance du contrôleur.
                if (method_exists($controllerInstance, $method)) {
                    // Appelle la méthode du contrôleur en utilisant call_user_func_array.
                    // Les paramètres ($params) sont passés à la méthode (actuellement vides).
                    echo call_user_func_array([$controllerInstance, $method], $params);
                } else {
                    // Si la méthode n'existe pas, affiche une erreur 500.
                    $this->error500("Method {$method} not found in {$controllerName}");
                }
            } catch (\Exception $e) {
                // En cas d'erreur lors de l'instanciation du contrôleur, affiche une erreur 500.
                $this->error500("Error instantiating controller {$controllerName}: " . $e->getMessage());
            }
        } else {
            // Si la classe du contrôleur n'existe pas, affiche une erreur 500.
            $this->error500("Controller class {$controllerClass} not found");
        }
    }

    /**
     * Méthode privée pour afficher une erreur 404 (Page non trouvée).
     * Définit le code de réponse HTTP à 404 et affiche un message.
     *
     * @return void
     */
    private function error404()
    {
        http_response_code(404);
        echo "404 La page est introuvable 😕";
    }

    /**
     * Méthode privée pour afficher une erreur 500 (Erreur interne du serveur).
     * Définit le code de réponse HTTP à 500 et affiche un message d'erreur.
     *
     * @param string $message Le message d'erreur à afficher.
     * @return void
     */
    private function error500($message)
    {
        http_response_code(500);
        echo "500 Une erreur inattendue est survenue 😕: " . $message;
    }
}

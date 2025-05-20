<?php

use App\Core\Router;

require_once __DIR__ . '/../app/Core/bootstrap.php';

$router = $GLOBALS['injector']->resolve(Router::class);
// Récupération de l'URI et de la méthode HTTP
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Dispatch de la route
$router->dispatch($uri, $method);

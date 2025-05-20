<?php

use App\Core\DependencyInjector;
use App\Helpers\GlobalErrorHandler;
use App\Providers\ServiceProvider;

$_ENV['APP_ENV'] = 'development';

require_once __DIR__ . '/../../vendor/autoload.php';

$injector = new DependencyInjector();
//Enregistrement des Services
$serviceProvider = new ServiceProvider();
$serviceProvider->register($injector);

// Enregistrement des gestionnaires d'erreurs / exceptions globaux
try {
    set_exception_handler([$injector->resolve(GlobalErrorHandler::class), 'handleException']);
    set_error_handler([$injector->resolve(GlobalErrorHandler::class), 'handleError']);
    register_shutdown_function([$injector->resolve(GlobalErrorHandler::class), 'handleFatalError']);
} catch (Exception $e) {
    echo "Erreur lors de l'enregistrement des gestionnaires d'erreurs : " . $e->getMessage() . "\n";
}

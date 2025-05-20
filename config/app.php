<?php

return [
    'logFilePath' => dirname(__DIR__) . '/logs/' . 'app_' . date('Y-m-d') . '.log',
    'controllers' => "App\\Http\\Controllers",
    'templates_path' => [
        'index' => dirname(__DIR__) . '/resources/views/templates/car/index.php',
        'alert' => dirname(__DIR__) . '/resources/views/templates/partials/alert.php',
        'delete' => dirname(__DIR__) . '/resources/views/templates/partials/modal/delete.php',
        'details' => dirname(__DIR__) . '/resources/views/templates/partials/modal/details.php',
    ],
    'phpQuery' => dirname(__DIR__) . '/lib/phpQuery/phpQuery/phpQuery.php',
];

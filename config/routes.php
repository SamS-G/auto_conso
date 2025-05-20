<?php

return [
    'GET' => [
        '/vehicules/delete-confirm' => 'CarModelController@deleteConfirm',
        '/vehicules' => 'CarModelController@index',
        '/vehicules/details' => 'CarModelController@details',
        '/seed' => 'SeedController@seed',
    ],
    'POST' => [
        '/vehicules/search' => 'CarModelController@search',
    ],
    'DELETE' => [
        '/vehicules/delete' => 'CarModelController@delete',
    ]
];

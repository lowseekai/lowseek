<?php

return [
    'debug' => false,
    'database' => [
        'driver' => 'mysql',
        'host' => 'mysql',
        'port' => 3306,
        'database' => 'lowseek_flarum',
        'username' => 'lowseek_flarum',
        'password' => 'CHANGE_ME',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => false,
        'engine' => 'InnoDB',
        'prefix_indexes' => true,
    ],
    'url' => 'https://test.iseekup.com',
    'paths' => [
        'api' => 'api',
        'admin' => 'admin',
    ],
    'headers' => [
        'poweredByHeader' => true,
        'referrerPolicy' => 'same-origin',
    ],
];

<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Q to me',
        'base_url' => 'https://q-2.me',
        'timezone' => 'UTC',
        'secret_salt' => 'change-this-long-random-secret',
    ],
    'db' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=qrcode;charset=utf8mb4',
        'user' => 'root',
        'password' => '',
    ],
    'mail' => [
        'from' => 'no-reply@example.com',
        'admin_to' => '',
        'smtp' => [
            'host' => '',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
        ],
    ],
];

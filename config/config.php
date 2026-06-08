<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'QR Moderation',
        'base_url' => 'http://localhost',
        'timezone' => 'UTC',
        'secret_salt' => 'change-this-long-random-secret',
    ],
    'db' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=qrcode;charset=utf8mb4',
        'user' => 'root',
        'password' => '',
    ],
];

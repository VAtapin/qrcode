<?php

declare(strict_types=1);

/**
 * Q to me - moderated short link and QR code service.
 *
 * @author Atapin Vladimir <atapin@gmail.com>
 * @link https://bible-media.de/
 * @copyright 2026 Atapin Vladimir / Bible Media
 * @version 1.0.0
 */

return [
    'app' => [
        'name' => 'Q to me',
        'base_url' => 'https://q-2.me',
        'timezone' => 'UTC',
        'secret_salt' => 'change-this-long-random-secret',
        'default_locale' => 'de',
    ],
    'db' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=qrcode;charset=utf8mb4',
        'user' => 'root',
        'password' => '',
    ],
    'mail' => [
        'from' => 'no-reply@example.com',
        'smtp' => [
            'host' => '',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
        ],
    ],
];

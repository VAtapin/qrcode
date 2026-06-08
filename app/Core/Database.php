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

namespace App\Core;

use PDO;

/**
 * Stores database configuration and provides a shared PDO connection.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static string $dsn = '';
    private static string $user = '';
    private static string $password = '';

    /**
     * Stores database credentials for lazy PDO initialization.
     *
     * @param string $dsn PDO DSN.
     * @param string $user Database user.
     * @param string $password Database password.
     */
    public static function configure(string $dsn, string $user, string $password): void
    {
        self::$dsn = $dsn;
        self::$user = $user;
        self::$password = $password;
    }

    /**
     * Returns the shared PDO connection.
     */
    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO(self::$dsn, self::$user, self::$password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$pdo;
    }
}

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

namespace App\Models;

use App\Core\Database;

/**
 * Provides database access for administrator accounts.
 */
final class Admin
{
    /**
     * Finds an administrator by login.
     *
     * @return array<string, mixed>|null
     */
    public static function findByLogin(string $login): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM admins WHERE login = :login LIMIT 1');
        $stmt->execute(['login' => $login]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Finds an administrator by identifier.
     *
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM admins WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Creates a new administrator account.
     */
    public static function create(string $login, string $passwordHash): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO admins (login, password_hash, locale, created_at) VALUES (:login, :password_hash, :locale, NOW())'
        );
        $stmt->execute(['login' => $login, 'password_hash' => $passwordHash, 'locale' => default_locale()]);
    }

    /**
     * Stores the latest successful login timestamp.
     */
    public static function touchLogin(int $id): void
    {
        $stmt = Database::pdo()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Stores the preferred interface language for an administrator.
     */
    public static function updateLocale(int $id, string $locale): void
    {
        $stmt = Database::pdo()->prepare('UPDATE admins SET locale = :locale WHERE id = :id');
        $stmt->execute(['id' => $id, 'locale' => $locale]);
    }

    /**
     * Returns the locale used for administrator notifications.
     */
    public static function notificationLocale(): string
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT locale FROM admins ORDER BY id ASC LIMIT 1');
            $stmt->execute();
            $locale = (string) ($stmt->fetchColumn() ?: default_locale());
        } catch (\Throwable) {
            $locale = default_locale();
        }

        return in_array($locale, supported_locales(), true) ? $locale : default_locale();
    }
}

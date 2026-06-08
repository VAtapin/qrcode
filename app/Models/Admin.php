<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Admin
{
    public static function findByLogin(string $login): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM admins WHERE login = :login LIMIT 1');
        $stmt->execute(['login' => $login]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM admins WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $login, string $passwordHash): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO admins (login, password_hash, created_at) VALUES (:login, :password_hash, NOW())'
        );
        $stmt->execute(['login' => $login, 'password_hash' => $passwordHash]);
    }

    public static function touchLogin(int $id): void
    {
        $stmt = Database::pdo()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}

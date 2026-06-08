<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class BlacklistWord
{
    public static function all(): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM blacklist_words ORDER BY word ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function isBlocked(string $word): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM blacklist_words WHERE LOWER(word) = LOWER(:word)');
        $stmt->execute(['word' => $word]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function add(string $word): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT IGNORE INTO blacklist_words (word, created_at) VALUES (:word, NOW())'
        );
        $stmt->execute(['word' => $word]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM blacklist_words WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}

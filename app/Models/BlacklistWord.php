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
 * Provides database access for forbidden short-code words.
 */
final class BlacklistWord
{
    /**
     * Returns all blacklist words ordered alphabetically.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM blacklist_words ORDER BY word ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Checks whether a short-code word is blocked.
     */
    public static function isBlocked(string $word): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM blacklist_words WHERE LOWER(word) = LOWER(:word)');
        $stmt->execute(['word' => $word]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Adds a word to the blacklist.
     */
    public static function add(string $word): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT IGNORE INTO blacklist_words (word, created_at) VALUES (:word, NOW())'
        );
        $stmt->execute(['word' => $word]);
    }

    /**
     * Deletes a blacklist word by identifier.
     */
    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM blacklist_words WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}

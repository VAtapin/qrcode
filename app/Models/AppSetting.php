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
 * Provides database access for editable application settings.
 */
final class AppSetting
{
    /**
     * Returns all stored settings as key-value pairs.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $stmt = Database::pdo()->prepare('SELECT setting_key, setting_value FROM app_settings ORDER BY setting_key ASC');
        $stmt->execute();

        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }

        return $settings;
    }

    /**
     * Returns one setting value or null when no value is stored.
     */
    public static function get(string $key): ?string
    {
        $stmt = Database::pdo()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :setting_key LIMIT 1');
        $stmt->execute(['setting_key' => $key]);
        $value = $stmt->fetchColumn();

        return $value === false ? null : (string) $value;
    }

    /**
     * Stores several settings.
     *
     * @param array<string, string> $settings Settings to store.
     */
    public static function setMany(array $settings): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO app_settings (setting_key, setting_value, updated_at)
             VALUES (:setting_key, :setting_value, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );

        foreach ($settings as $key => $value) {
            $stmt->execute([
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        }
    }
}

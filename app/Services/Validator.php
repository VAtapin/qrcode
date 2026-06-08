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

namespace App\Services;

use App\Models\BlacklistWord;

/**
 * Validates user-submitted link fields.
 */
final class Validator
{
    /**
     * Validates a link title.
     */
    public static function title(string $title): ?string
    {
        $length = mb_strlen(trim($title));
        return $length >= 2 && $length <= 190 ? null : 'Название должно быть от 2 до 190 символов.';
    }

    /**
     * Validates the destination URL and rejects local or private addresses.
     */
    public static function url(string $url): ?string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return 'URL имеет неверный формат.';
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true)) {
            return 'Разрешены только ссылки http и https.';
        }

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            return 'Локальные адреса запрещены.';
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'Внутренние и служебные IP-адреса запрещены.';
        }

        return null;
    }

    /**
     * Validates the requested short code against format and blacklist rules.
     */
    public static function code(string $code): ?string
    {
        if (!preg_match('/^[A-Za-z0-9*_]{3,50}$/', $code)) {
            return 'Короткий код должен содержать 3-50 символов: a-z, A-Z, 0-9, * или _.';
        }

        if (BlacklistWord::isBlocked($code)) {
            return 'Этот короткий код зарезервирован.';
        }

        return null;
    }

    /**
     * Validates a HEX color value.
     */
    public static function color(string $color): ?string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? null : 'Цвет QR-кода должен быть HEX вида #000000.';
    }
}

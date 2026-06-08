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
        return $length >= 2 && $length <= 190 ? null : __('validation.title');
    }

    /**
     * Validates the destination URL and rejects local or private addresses.
     */
    public static function url(string $url): ?string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return __('validation.url');
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true)) {
            return __('validation.http_only');
        }

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            return __('validation.local_forbidden');
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return __('validation.private_ip_forbidden');
        }

        return null;
    }

    /**
     * Validates the requested short code against format and blacklist rules.
     */
    public static function code(string $code): ?string
    {
        if (!preg_match('/^[A-Za-z0-9*_]{3,50}$/', $code)) {
            return __('validation.code');
        }

        if (BlacklistWord::isBlocked($code)) {
            return __('validation.code_reserved');
        }

        return null;
    }

    /**
     * Validates a HEX color value.
     */
    public static function color(string $color): ?string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? null : __('validation.color');
    }
}

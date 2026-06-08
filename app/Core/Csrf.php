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

/**
 * Provides CSRF token generation, hidden form fields, and token verification.
 */
final class Csrf
{
    /**
     * Returns the current session CSRF token or creates a new one.
     */
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    /**
     * Builds the hidden CSRF input field for HTML forms.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    /**
     * Verifies the submitted CSRF token against the session token.
     */
    public static function verify(): bool
    {
        $token = $_POST['_csrf'] ?? '';
        return is_string($token) && hash_equals($_SESSION['_csrf'] ?? '', $token);
    }
}

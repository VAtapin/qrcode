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

namespace App\Middleware;

/**
 * Protects administrator-only routes.
 */
final class AuthMiddleware
{
    /**
     * Redirects anonymous visitors to the login page.
     */
    public static function requireAdmin(): void
    {
        if (empty($_SESSION['admin_id'])) {
            redirect('/login');
        }

        if (
            empty($_SESSION['_locale'])
            && !empty($_SESSION['admin_locale'])
            && in_array($_SESSION['admin_locale'], supported_locales(), true)
        ) {
            app_locale((string) $_SESSION['admin_locale'], false);
        }
    }
}

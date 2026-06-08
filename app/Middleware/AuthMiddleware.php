<?php

declare(strict_types=1);

namespace App\Middleware;

final class AuthMiddleware
{
    public static function requireAdmin(): void
    {
        if (empty($_SESSION['admin_id'])) {
            redirect('/login');
        }
    }
}

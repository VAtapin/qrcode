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

use App\Models\Admin;

require dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from CLI.\n");
    exit(1);
}

[$script, $login, $password] = array_pad($argv, 3, null);
if (!$login || !$password) {
    fwrite(STDERR, "Usage: php database/create_admin.php admin password\n");
    exit(1);
}

if (Admin::findByLogin($login) !== null) {
    fwrite(STDERR, "Admin already exists.\n");
    exit(1);
}

Admin::create($login, password_hash($password, PASSWORD_DEFAULT));
fwrite(STDOUT, "Admin created.\n");

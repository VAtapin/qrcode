<?php

declare(strict_types=1);

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

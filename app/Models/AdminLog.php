<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AdminLog
{
    public static function write(int $adminId, string $action, ?int $linkId = null): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO admin_logs (admin_id, action, link_id, created_at, ip_hash)
             VALUES (:admin_id, :action, :link_id, NOW(), :ip_hash)'
        );
        $stmt->execute([
            'admin_id' => $adminId,
            'action' => $action,
            'link_id' => $linkId,
            'ip_hash' => ip_hash(),
        ]);
    }
}

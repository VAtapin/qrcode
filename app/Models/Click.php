<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Click
{
    public static function record(int $linkId, string $ipHash, ?string $userAgent, ?string $referer): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO qr_clicks (link_id, clicked_at, ip_hash, user_agent, referer)
             VALUES (:link_id, NOW(), :ip_hash, :user_agent, :referer)'
        );
        $stmt->execute([
            'link_id' => $linkId,
            'ip_hash' => $ipHash,
            'user_agent' => mb_substr((string) $userAgent, 0, 1000),
            'referer' => mb_substr((string) $referer, 0, 1000),
        ]);
    }
}

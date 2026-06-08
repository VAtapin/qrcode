<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Link
{
    public static function create(array $data): int
    {
        $sql = 'INSERT INTO qr_links
            (short_code, title, target_url, qr_color, status, comment, created_at, updated_at, created_ip_hash)
            VALUES (:short_code, :title, :target_url, :qr_color, "pending", :comment, NOW(), NOW(), :created_ip_hash)';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($data);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateQrPath(int $id, string $path): void
    {
        $stmt = Database::pdo()->prepare('UPDATE qr_links SET qr_path = :qr_path, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id, 'qr_path' => $path]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM qr_links WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM qr_links WHERE short_code = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        return $stmt->fetch() ?: null;
    }

    public static function codeExists(string $code, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM qr_links WHERE short_code = :code';
        $params = ['code' => $code];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function listByStatus(string $status): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT l.*, (SELECT COUNT(*) FROM qr_links d WHERE d.target_url = l.target_url AND d.id <> l.id) AS duplicates
             FROM qr_links l WHERE l.status = :status ORDER BY l.created_at DESC'
        );
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll();
    }

    public static function stats(): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT
                SUM(status = "pending") AS pending,
                SUM(status = "approved") AS approved,
                SUM(status = "rejected") AS rejected,
                SUM(status = "blocked") AS blocked,
                (SELECT COUNT(*) FROM qr_clicks) AS clicks
             FROM qr_links'
        );
        $stmt->execute();
        $row = $stmt->fetch() ?: [];
        return array_map('intval', [
            'pending' => $row['pending'] ?? 0,
            'approved' => $row['approved'] ?? 0,
            'rejected' => $row['rejected'] ?? 0,
            'blocked' => $row['blocked'] ?? 0,
            'clicks' => $row['clicks'] ?? 0,
        ]);
    }

    public static function countRecentByIpHash(string $ipHash): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM qr_links WHERE created_ip_hash = :ip_hash AND created_at >= (NOW() - INTERVAL 10 MINUTE)'
        );
        $stmt->execute(['ip_hash' => $ipHash]);
        return (int) $stmt->fetchColumn();
    }

    public static function update(int $id, array $data): void
    {
        $sql = 'UPDATE qr_links SET short_code = :short_code, title = :title, target_url = :target_url,
                qr_color = :qr_color, comment = :comment, admin_note = :admin_note, updated_at = NOW()
                WHERE id = :id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($data + ['id' => $id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        $fields = [
            'approved' => 'approved_at = NOW(), rejected_at = NULL',
            'rejected' => 'rejected_at = NOW()',
            'blocked' => 'updated_at = NOW()',
            'pending' => 'approved_at = NULL, rejected_at = NULL',
        ];
        $sql = 'UPDATE qr_links SET status = :status, updated_at = NOW(), ' . $fields[$status] . ' WHERE id = :id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM qr_links WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}

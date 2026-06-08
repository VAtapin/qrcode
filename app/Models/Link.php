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

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Provides database access for short links, QR metadata, gallery, and moderation.
 */
final class Link
{
    /**
     * Creates a new short-link record.
     *
     * @param array<string, mixed> $data Link fields.
     */
    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO qr_links
             (short_code, title, target_url, qr_color, status, is_public, submitter_email, locale, comment, created_at, updated_at, approved_at, created_ip_hash)
             VALUES (:short_code, :title, :target_url, :qr_color, :status, :is_public, :submitter_email, :locale, :comment, NOW(), NOW(), :approved_at, :created_ip_hash)'
        );
        $stmt->execute($data);
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Stores the generated QR image path for a link.
     */
    public static function updateQrPath(int $id, string $path): void
    {
        $stmt = Database::pdo()->prepare('UPDATE qr_links SET qr_path = :qr_path, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id, 'qr_path' => $path]);
    }

    /**
     * Finds a link by database identifier.
     *
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM qr_links WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Finds a link by short code.
     *
     * @return array<string, mixed>|null
     */
    public static function findByCode(string $code): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM qr_links WHERE short_code = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Returns paginated gallery items with total page metadata.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int}
     */
    public static function gallery(string $search, string $filter, int $page, int $perPage, bool $includePrivate): array
    {
        $where = ['status = "approved"'];
        $params = [];

        if (!$includePrivate) {
            $where[] = 'is_public = 1';
        } elseif ($filter === 'public') {
            $where[] = 'is_public = 1';
        } elseif ($filter === 'private') {
            $where[] = 'is_public = 0';
        }

        if ($search !== '') {
            $where[] = '(LOWER(title) LIKE :search OR LOWER(short_code) LIKE :search)';
            $params['search'] = '%' . mb_strtolower($search) . '%';
        }

        $order = $filter === 'latest' ? 'created_at DESC' : 'title ASC, created_at DESC';
        $whereSql = implode(' AND ', $where);

        $count = Database::pdo()->prepare('SELECT COUNT(*) FROM qr_links WHERE ' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $stmt = Database::pdo()->prepare(
            'SELECT * FROM qr_links WHERE ' . $whereSql . ' ORDER BY ' . $order . ' LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', max(0, ($page - 1) * $perPage), PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Checks whether a short code already exists.
     */
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

    /**
     * Lists links by moderation status with click and duplicate metadata.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function listByStatus(string $status): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT l.*,
                    (SELECT COUNT(*) FROM qr_links d WHERE d.target_url = l.target_url AND d.id <> l.id) AS duplicates,
                    (SELECT COUNT(*) FROM qr_clicks c WHERE c.link_id = l.id) AS click_count,
                    (SELECT MAX(c.clicked_at) FROM qr_clicks c WHERE c.link_id = l.id) AS last_clicked_at
             FROM qr_links l
             WHERE l.status = :status
             ORDER BY l.created_at DESC'
        );
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll();
    }

    /**
     * Returns aggregate dashboard statistics.
     *
     * @return array{pending: int, approved: int, rejected: int, blocked: int, clicks: int}
     */
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

    /**
     * Counts recent submissions from the same IP hash.
     */
    public static function countRecentByIpHash(string $ipHash): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM qr_links WHERE created_ip_hash = :ip_hash AND created_at >= (NOW() - INTERVAL 10 MINUTE)'
        );
        $stmt->execute(['ip_hash' => $ipHash]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Counts daily submissions from the same IP hash.
     */
    public static function countDailyByIpHash(string $ipHash): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM qr_links WHERE created_ip_hash = :ip_hash AND created_at >= (NOW() - INTERVAL 1 DAY)'
        );
        $stmt->execute(['ip_hash' => $ipHash]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Updates editable link fields.
     *
     * @param array<string, mixed> $data Link fields.
     */
    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE qr_links SET short_code = :short_code, title = :title, target_url = :target_url,
                qr_color = :qr_color, is_public = :is_public, submitter_email = :submitter_email,
                comment = :comment, admin_note = :admin_note, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute($data + ['id' => $id]);
    }

    /**
     * Sets the moderation status and returns the updated row.
     *
     * @return array<string, mixed>|null
     */
    public static function setStatus(int $id, string $status): ?array
    {
        $fields = [
            'approved' => 'approved_at = NOW(), rejected_at = NULL',
            'rejected' => 'rejected_at = NOW()',
            'blocked' => 'updated_at = NOW()',
            'pending' => 'approved_at = NULL, rejected_at = NULL',
        ];
        $stmt = Database::pdo()->prepare(
            'UPDATE qr_links SET status = :status, updated_at = NOW(), ' . $fields[$status] . ' WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'id' => $id]);
        return self::find($id);
    }

    /**
     * Deletes a link and removes its generated QR image when present.
     */
    public static function delete(int $id): void
    {
        $link = self::find($id);
        $stmt = Database::pdo()->prepare('DELETE FROM qr_links WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($link !== null && !empty($link['qr_path'])) {
            $path = STORAGE_PATH . '/' . $link['qr_path'];
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}

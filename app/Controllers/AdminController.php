<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Middleware\AuthMiddleware;
use App\Models\AdminLog;
use App\Models\Link;
use App\Services\QrService;
use App\Services\Validator;

final class AdminController
{
    private const STATUSES = ['pending', 'approved', 'rejected', 'blocked'];

    public function dashboard(): void
    {
        AuthMiddleware::requireAdmin();
        view('admin/dashboard', ['title' => 'Админка', 'stats' => Link::stats()]);
    }

    public function listPending(): void { $this->list('pending'); }
    public function listApproved(): void { $this->list('approved'); }
    public function listRejected(): void { $this->list('rejected'); }
    public function listBlocked(): void { $this->list('blocked'); }

    public function edit(string $id): void
    {
        AuthMiddleware::requireAdmin();
        $link = Link::find((int) $id);
        if ($link === null) {
            http_response_code(404);
            view('errors/message', ['title' => 'Ссылка не найдена', 'message' => 'Запись отсутствует.']);
            return;
        }
        view('admin/edit', ['title' => 'Редактирование', 'link' => $link]);
    }

    public function update(string $id): void
    {
        AuthMiddleware::requireAdmin();
        $link = Link::find((int) $id);
        if ($link === null || !Csrf::verify()) {
            flash('error', 'Не удалось сохранить изменения.');
            redirect('/admin');
        }

        $data = [
            'short_code' => trim((string) ($_POST['short_code'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'target_url' => trim((string) ($_POST['target_url'] ?? '')),
            'qr_color' => trim((string) ($_POST['qr_color'] ?? '#000000')),
            'comment' => trim((string) ($_POST['comment'] ?? '')) ?: null,
            'admin_note' => trim((string) ($_POST['admin_note'] ?? '')) ?: null,
        ];

        $errors = array_filter([
            Validator::code($data['short_code']),
            Validator::title($data['title']),
            Validator::url($data['target_url']),
            Validator::color($data['qr_color']),
            Link::codeExists($data['short_code'], (int) $id) ? 'Этот короткий код уже занят.' : null,
        ]);

        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            redirect('/admin/edit/' . (int) $id);
        }

        Link::update((int) $id, $data);
        Link::updateQrPath((int) $id, (new QrService())->generate($data['short_code'], $data['qr_color']));
        AdminLog::write((int) $_SESSION['admin_id'], 'updated', (int) $id);
        flash('success', 'Изменения сохранены.');
        redirect('/admin/edit/' . (int) $id);
    }

    public function status(string $id): void
    {
        AuthMiddleware::requireAdmin();
        $status = (string) ($_POST['status'] ?? '');
        if (!Csrf::verify() || !in_array($status, self::STATUSES, true)) {
            flash('error', 'Некорректное действие.');
            redirect('/admin');
        }

        Link::setStatus((int) $id, $status);
        AdminLog::write((int) $_SESSION['admin_id'], $status, (int) $id);
        flash('success', 'Статус обновлен.');
        redirect('/admin/' . $status);
    }

    public function delete(string $id): void
    {
        AuthMiddleware::requireAdmin();
        if (!Csrf::verify()) {
            flash('error', 'Некорректный CSRF-токен.');
            redirect('/admin');
        }

        Link::delete((int) $id);
        AdminLog::write((int) $_SESSION['admin_id'], 'deleted', (int) $id);
        flash('success', 'Запись удалена.');
        redirect('/admin');
    }

    private function list(string $status): void
    {
        AuthMiddleware::requireAdmin();
        view('admin/list', [
            'title' => ucfirst($status),
            'status' => $status,
            'links' => Link::listByStatus($status),
        ]);
    }
}

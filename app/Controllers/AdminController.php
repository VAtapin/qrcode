<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Middleware\AuthMiddleware;
use App\Models\AdminLog;
use App\Models\BlacklistWord;
use App\Models\Link;
use App\Services\MailService;
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
        $returnTo = $this->safeReturnTo('/admin/edit/' . (int) $id);
        $link = Link::find((int) $id);
        if ($link === null || !Csrf::verify()) {
            flash('error', 'Не удалось сохранить изменения.');
            redirect($returnTo);
        }

        $data = [
            'short_code' => trim((string) ($_POST['short_code'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'target_url' => trim((string) ($_POST['target_url'] ?? '')),
            'qr_color' => trim((string) ($_POST['qr_color'] ?? '#000000')),
            'is_public' => isset($_POST['is_public']) ? 1 : 0,
            'submitter_email' => trim((string) ($_POST['submitter_email'] ?? '')) ?: null,
            'comment' => trim((string) ($_POST['comment'] ?? '')) ?: null,
            'admin_note' => trim((string) ($_POST['admin_note'] ?? '')) ?: null,
        ];

        $emailError = $data['submitter_email'] !== null && !filter_var($data['submitter_email'], FILTER_VALIDATE_EMAIL)
            ? 'E-mail имеет неверный формат.'
            : null;

        $errors = array_filter([
            Validator::code($data['short_code']),
            Validator::title($data['title']),
            Validator::url($data['target_url']),
            Validator::color($data['qr_color']),
            $emailError,
            Link::codeExists($data['short_code'], (int) $id) ? 'Этот короткий код уже занят.' : null,
        ]);

        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            redirect($returnTo);
        }

        Link::update((int) $id, $data);
        Link::updateQrPath((int) $id, (new QrService())->generate($data['short_code'], $data['qr_color']));
        $updatedLink = Link::find((int) $id);
        if ($updatedLink !== null) {
            (new MailService())->sendLinkUpdated($updatedLink);
        }
        AdminLog::write((int) $_SESSION['admin_id'], 'updated', (int) $id);
        flash('success', 'Изменения сохранены.');
        redirect($returnTo);
    }

    public function status(string $id): void
    {
        AuthMiddleware::requireAdmin();
        $returnTo = $this->safeReturnTo('/admin');
        $status = (string) ($_POST['status'] ?? '');
        if (!Csrf::verify() || !in_array($status, self::STATUSES, true)) {
            flash('error', 'Некорректное действие.');
            redirect($returnTo);
        }

        $link = Link::setStatus((int) $id, $status);
        AdminLog::write((int) $_SESSION['admin_id'], $status, (int) $id);

        if ($link !== null) {
            (new MailService())->sendLinkStatusChanged($link, $status);
        }

        flash('success', 'Статус обновлен.');
        redirect($returnTo);
    }

    public function delete(string $id): void
    {
        AuthMiddleware::requireAdmin();
        $returnTo = $this->safeReturnTo('/admin');
        if (!Csrf::verify()) {
            flash('error', 'Некорректный CSRF-токен.');
            redirect($returnTo);
        }

        $link = Link::find((int) $id);
        if ($link !== null) {
            (new MailService())->sendLinkDeleted($link);
        }
        AdminLog::write((int) $_SESSION['admin_id'], 'deleted', null);
        Link::delete((int) $id);
        flash('success', 'Запись удалена.');
        redirect($returnTo);
    }

    public function blacklist(): void
    {
        AuthMiddleware::requireAdmin();
        view('admin/blacklist', [
            'title' => 'Чёрный список',
            'words' => BlacklistWord::all(),
        ]);
    }

    public function blacklistAdd(): void
    {
        AuthMiddleware::requireAdmin();
        $word = trim((string) ($_POST['word'] ?? ''));
        if (!Csrf::verify() || !preg_match('/^[A-Za-z0-9*_]{3,50}$/', $word)) {
            flash('error', 'Слово должно быть коротким кодом длиной 3-50 символов.');
            redirect('/admin/blacklist');
        }

        BlacklistWord::add($word);
        flash('success', 'Слово добавлено.');
        redirect('/admin/blacklist');
    }

    public function blacklistDelete(string $id): void
    {
        AuthMiddleware::requireAdmin();
        if (!Csrf::verify()) {
            flash('error', 'Некорректный CSRF-токен.');
            redirect('/admin/blacklist');
        }

        BlacklistWord::delete((int) $id);
        flash('success', 'Слово удалено.');
        redirect('/admin/blacklist');
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

    private function safeReturnTo(string $default): string
    {
        $returnTo = (string) ($_POST['return_to'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
        $path = parse_url($returnTo, PHP_URL_PATH);
        $query = parse_url($returnTo, PHP_URL_QUERY);
        if (!is_string($path) || $path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return $default;
        }

        return $query ? $path . '?' . $query : $path;
    }
}

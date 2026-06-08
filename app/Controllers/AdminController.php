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

namespace App\Controllers;

use App\Core\Csrf;
use App\Middleware\AuthMiddleware;
use App\Models\AdminLog;
use App\Models\BlacklistWord;
use App\Models\Link;
use App\Services\MailService;
use App\Services\QrService;
use App\Services\Validator;

/**
 * Handles administrator dashboards, moderation, edits, deletion, and blacklist actions.
 */
final class AdminController
{
    private const STATUSES = ['pending', 'approved', 'rejected', 'blocked'];

    /**
     * Shows the administrator dashboard with aggregate statistics.
     */
    public function dashboard(): void
    {
        AuthMiddleware::requireAdmin();
        view('admin/dashboard', ['title' => __('admin.dashboard'), 'stats' => Link::stats()]);
    }

    /** Shows pending links. */
    public function listPending(): void { $this->list('pending'); }
    /** Shows approved links. */
    public function listApproved(): void { $this->list('approved'); }
    /** Shows rejected links. */
    public function listRejected(): void { $this->list('rejected'); }
    /** Shows blocked links. */
    public function listBlocked(): void { $this->list('blocked'); }

    /**
     * Shows the edit form for one link.
     *
     * @param string $id Link identifier from the route.
     */
    public function edit(string $id): void
    {
        AuthMiddleware::requireAdmin();
        $link = Link::find((int) $id);
        if ($link === null) {
            http_response_code(404);
            view('errors/message', ['title' => __('error.link_not_found'), 'message' => __('error.code_not_found')]);
            return;
        }
        view('admin/edit', ['title' => __('button.edit'), 'link' => $link]);
    }

    /**
     * Validates and saves administrator edits for one link.
     *
     * @param string $id Link identifier from the route.
     */
    public function update(string $id): void
    {
        AuthMiddleware::requireAdmin();
        $returnTo = $this->safeReturnTo('/admin/edit/' . (int) $id);
        $link = Link::find((int) $id);
        if ($link === null || !Csrf::verify()) {
            flash('error', __('flash.save_failed'));
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
            ? __('flash.email_invalid')
            : null;

        $errors = array_filter([
            Validator::code($data['short_code']),
            Validator::title($data['title']),
            Validator::url($data['target_url']),
            Validator::color($data['qr_color']),
            $emailError,
            Link::codeExists($data['short_code'], (int) $id) ? __('error.code_exists') : null,
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
        flash('success', __('flash.saved'));
        redirect($returnTo);
    }

    /**
     * Changes moderation status and sends the corresponding user notification.
     *
     * @param string $id Link identifier from the route.
     */
    public function status(string $id): void
    {
        AuthMiddleware::requireAdmin();
        $returnTo = $this->safeReturnTo('/admin');
        $status = (string) ($_POST['status'] ?? '');
        if (!Csrf::verify() || !in_array($status, self::STATUSES, true)) {
            flash('error', __('flash.invalid_action'));
            redirect($returnTo);
        }

        $link = Link::setStatus((int) $id, $status);
        AdminLog::write((int) $_SESSION['admin_id'], $status, (int) $id);

        if ($link !== null) {
            (new MailService())->sendLinkStatusChanged($link, $status);
        }

        flash('success', __('flash.status_updated'));
        redirect($returnTo);
    }

    /**
     * Deletes one link and its generated QR image.
     *
     * @param string $id Link identifier from the route.
     */
    public function delete(string $id): void
    {
        AuthMiddleware::requireAdmin();
        $returnTo = $this->safeReturnTo('/admin');
        if (!Csrf::verify()) {
            flash('error', __('flash.invalid_csrf'));
            redirect($returnTo);
        }

        $link = Link::find((int) $id);
        if ($link !== null) {
            (new MailService())->sendLinkDeleted($link);
        }
        AdminLog::write((int) $_SESSION['admin_id'], 'deleted', null);
        Link::delete((int) $id);
        flash('success', __('flash.deleted'));
        redirect($returnTo);
    }

    /**
     * Shows editable blacklist words.
     */
    public function blacklist(): void
    {
        AuthMiddleware::requireAdmin();
        view('admin/blacklist', [
            'title' => __('blacklist.title'),
            'words' => BlacklistWord::all(),
        ]);
    }

    /**
     * Adds a word to the short-code blacklist.
     */
    public function blacklistAdd(): void
    {
        AuthMiddleware::requireAdmin();
        $word = trim((string) ($_POST['word'] ?? ''));
        if (!Csrf::verify() || !preg_match('/^[A-Za-z0-9*_]{3,50}$/', $word)) {
            flash('error', __('flash.blacklist_invalid'));
            redirect('/admin/blacklist');
        }

        BlacklistWord::add($word);
        flash('success', __('flash.blacklist_added'));
        redirect('/admin/blacklist');
    }

    /**
     * Deletes one word from the blacklist.
     *
     * @param string $id Blacklist word identifier from the route.
     */
    public function blacklistDelete(string $id): void
    {
        AuthMiddleware::requireAdmin();
        if (!Csrf::verify()) {
            flash('error', __('flash.invalid_csrf'));
            redirect('/admin/blacklist');
        }

        BlacklistWord::delete((int) $id);
        flash('success', __('flash.blacklist_deleted'));
        redirect('/admin/blacklist');
    }

    /**
     * Renders links for the given moderation status.
     *
     * @param string $status Moderation status.
     */
    private function list(string $status): void
    {
        AuthMiddleware::requireAdmin();
        view('admin/list', [
            'title' => __('admin.' . $status),
            'status' => $status,
            'links' => Link::listByStatus($status),
        ]);
    }

    /**
     * Returns a safe internal redirect target for admin actions.
     *
     * @param string $default Fallback path.
     */
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

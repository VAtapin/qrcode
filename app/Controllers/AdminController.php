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
use App\Models\Admin;
use App\Models\AdminLog;
use App\Models\AppSetting;
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
     * Shows administrator settings.
     */
    public function settings(): void
    {
        AuthMiddleware::requireAdmin();
        $admin = Admin::find((int) $_SESSION['admin_id']);
        $legalLocale = app_locale();
        $settings = [
            'mail.admin_to' => setting('mail.admin_to', ''),
            'mail.from_name' => setting('mail.from_name', 'Q to me'),
            'legal.provider' => setting('legal.provider', legal_default_provider()),
            'legal.represented_by' => setting('legal.represented_by', ''),
            'legal.content_responsible' => setting('legal.content_responsible', ''),
            'legal.contact_email' => setting('legal.contact_email', legal_default_contact_email()),
            'legal.phone' => setting('legal.phone', legal_default_phone()),
            'legal.impressum_address' => setting('legal.impressum_address', legal_default_impressum_address()),
            'legal.impressum_text' => setting(
                'legal.impressum_text.' . $legalLocale,
                legal_default_impressum_text($legalLocale)
            ),
            'legal.privacy_text' => setting(
                'legal.privacy_text.' . $legalLocale,
                legal_default_privacy_text($legalLocale)
            ),
            'gallery.enabled' => setting('gallery.enabled', '1'),
        ];

        view('admin/settings', [
            'title' => __('admin.settings'),
            'admin' => $admin,
            'legalLocale' => $legalLocale,
            'settings' => $settings,
        ]);
    }

    /**
     * Saves administrator settings.
     */
    public function settingsUpdate(): void
    {
        AuthMiddleware::requireAdmin();
        $locale = (string) ($_POST['locale'] ?? default_locale());
        $legalLocale = (string) ($_POST['legal_locale'] ?? app_locale());
        $action = (string) ($_POST['settings_action'] ?? 'save');
        $adminTo = trim((string) ($_POST['mail_admin_to'] ?? ''));
        $fromName = trim((string) ($_POST['mail_from_name'] ?? 'Q to me'));
        $provider = trim((string) ($_POST['legal_provider'] ?? ''));
        $representedBy = trim((string) ($_POST['legal_represented_by'] ?? ''));
        $contentResponsible = trim((string) ($_POST['legal_content_responsible'] ?? ''));
        $contactEmail = trim((string) ($_POST['legal_contact_email'] ?? ''));
        $phone = trim((string) ($_POST['legal_phone'] ?? ''));
        $impressumAddress = trim((string) ($_POST['legal_impressum_address'] ?? ''));
        $galleryEnabled = isset($_POST['gallery_enabled']) ? '1' : '0';

        $emailError = (
            ($adminTo !== '' && !filter_var($adminTo, FILTER_VALIDATE_EMAIL))
            || ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL))
        );

        if (
            !Csrf::verify()
            || !in_array($locale, supported_locales(), true)
            || !in_array($legalLocale, supported_locales(), true)
            || $fromName === ''
            || $emailError
        ) {
            flash('error', __('flash.invalid_action'));
            redirect('/admin/settings');
        }

        Admin::updateLocale((int) $_SESSION['admin_id'], $locale);
        $settings = [
            'mail.admin_to' => $adminTo,
            'mail.from_name' => $fromName,
            'legal.provider' => $provider,
            'legal.represented_by' => $representedBy,
            'legal.content_responsible' => $contentResponsible,
            'legal.contact_email' => $contactEmail !== '' ? $contactEmail : legal_default_contact_email(),
            'legal.phone' => $phone !== '' ? $phone : legal_default_phone(),
            'legal.impressum_address' => $impressumAddress !== '' ? $impressumAddress : legal_default_impressum_address(),
            'gallery.enabled' => $galleryEnabled,
        ];
        if ($action === 'load_legal_defaults') {
            $settings['legal.impressum_text.' . $legalLocale] = legal_default_impressum_text($legalLocale);
            $settings['legal.privacy_text.' . $legalLocale] = legal_default_privacy_text($legalLocale);
        } else {
            $settings['legal.impressum_text.' . $legalLocale] = trim((string) ($_POST['legal_impressum_text'] ?? ''));
            $settings['legal.privacy_text.' . $legalLocale] = trim((string) ($_POST['legal_privacy_text'] ?? ''));
        }

        try {
            AppSetting::setMany($settings);
        } catch (\Throwable) {
            flash('error', __('flash.save_failed'));
            redirect('/admin/settings');
        }
        $_SESSION['admin_locale'] = $locale;
        app_locale($legalLocale);
        flash('success', $action === 'load_legal_defaults' ? __('flash.legal_defaults_loaded') : __('flash.settings_saved'));
        redirect('/admin/settings');
    }

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

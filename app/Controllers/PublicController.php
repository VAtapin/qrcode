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
use App\Models\Click;
use App\Models\Link;
use App\Services\MailService;
use App\Services\QrService;
use App\Services\RateLimiter;
use App\Services\Validator;

/**
 * Handles public gallery, link creation, QR pages, downloads, and redirects.
 */
final class PublicController
{
    /**
     * Shows the public QR-code gallery with search, filters, and pagination.
     */
    public function gallery(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $isAdmin = !empty($_SESSION['admin_id']);
        $filter = (string) ($_GET['filter'] ?? 'all');
        $allowedFilters = $isAdmin ? ['all', 'public', 'private', 'latest'] : ['all', 'latest'];
        $filter = in_array($filter, $allowedFilters, true) ? $filter : 'all';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        if (!$isAdmin && (string) setting('gallery.enabled', '1') !== '1') {
            view('public/gallery_disabled', [
                'title' => __('nav.gallery'),
            ]);
            return;
        }

        $gallery = Link::gallery($search, $filter, $page, $perPage, $isAdmin);

        view('public/gallery', [
            'title' => __('nav.gallery'),
            'items' => $gallery['items'],
            'total' => $gallery['total'],
            'page' => $gallery['page'],
            'pages' => $gallery['pages'],
            'search' => $search,
            'filter' => $filter,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Shows the public form for creating a new short link.
     */
    public function create(): void
    {
        view('public/create', ['title' => __('create.title')]);
    }

    /**
     * Shows the legal notice page.
     */
    public function impressum(): void
    {
        view('public/impressum', ['title' => 'Impressum']);
    }

    /**
     * Shows the privacy policy page.
     */
    public function datenschutz(): void
    {
        view('public/datenschutz', ['title' => 'Datenschutz']);
    }

    /**
     * Validates and stores a new short link, generates QR, and sends notifications.
     */
    public function store(): void
    {
        $submittedLocale = (string) ($_POST['locale'] ?? '');
        if (in_array($submittedLocale, supported_locales(), true)) {
            app_locale($submittedLocale, false);
        }

        if (!Csrf::verify()) {
            $this->validationError(__('error.session_expired'));
        }

        $isAdmin = !empty($_SESSION['admin_id']);
        $title = trim((string) ($_POST['title'] ?? ''));
        $targetUrl = trim((string) ($_POST['target_url'] ?? ''));
        $shortCode = trim((string) ($_POST['short_code'] ?? ''));
        $qrColor = trim((string) ($_POST['qr_color'] ?? '#000000')) ?: '#000000';
        $comment = trim((string) ($_POST['comment'] ?? ''));
        $submitterEmail = trim((string) ($_POST['submitter_email'] ?? ''));
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        $approveNow = $isAdmin && isset($_POST['approve_now']);
        $agree = isset($_POST['agree']);
        $honeypot = trim((string) ($_POST['website'] ?? ''));
        $startedAt = (int) ($_POST['form_started_at'] ?? 0);
        $ipHash = ip_hash();

        $errors = [];
        foreach ([Validator::title($title), Validator::url($targetUrl), Validator::color($qrColor)] as $error) {
            if ($error !== null) {
                $errors[] = $error;
            }
        }
        if ($shortCode === '') {
            $shortCode = $this->generateCode();
        } elseif (($error = Validator::code($shortCode)) !== null) {
            $errors[] = $error;
        }
        if (!$isAdmin && !filter_var($submitterEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('error.email_required');
        }
        if (!$agree) {
            $errors[] = __('error.agree_required');
        }
        if (Link::codeExists($shortCode)) {
            $errors[] = __('error.code_exists');
        }
        if (!$isAdmin && ($honeypot !== '' || RateLimiter::formWasSubmittedTooFast($startedAt))) {
            $errors[] = __('error.form_rejected');
        }
        if (!$isAdmin && RateLimiter::tooManySubmissions($ipHash)) {
            $errors[] = __('error.too_many');
        }
        if (!$isAdmin && RateLimiter::tooManyDailySubmissions($ipHash)) {
            $errors[] = __('error.daily_limit');
        }

        if ($errors !== []) {
            $this->validationError(implode(' ', $errors), $_POST);
        }

        $status = $approveNow ? 'approved' : 'pending';
        $id = Link::create([
            'short_code' => $shortCode,
            'title' => $title,
            'target_url' => $targetUrl,
            'qr_color' => $qrColor,
            'status' => $status,
            'is_public' => $isPublic,
            'submitter_email' => $submitterEmail !== '' ? $submitterEmail : null,
            'locale' => app_locale(),
            'comment' => $comment !== '' ? $comment : null,
            'approved_at' => $approveNow ? date('Y-m-d H:i:s') : null,
            'created_ip_hash' => $ipHash,
        ]);

        $qrPath = (new QrService())->generate($shortCode, $qrColor);
        Link::updateQrPath($id, $qrPath);

        $link = Link::find($id);
        if ($link !== null) {
            $mailer = new MailService();
            $mailer->sendLinkSubmitted($link);
            $mailer->sendAdminNewLink($link);
        }

        redirect(localized_path('result/' . $shortCode));
    }

    /**
     * Shows the result page after a QR code has been created.
     *
     * @param string $code Short link code.
     */
    public function result(string $code): void
    {
        $link = Link::findByCode($code);
        if ($link === null) {
            http_response_code(404);
            view('errors/message', ['title' => __('error.link_not_found'), 'message' => __('error.code_not_found')]);
            return;
        }

        view('public/result', ['title' => __('result.title'), 'link' => $link]);
    }

    /**
     * Shows a public QR page only for approved links.
     *
     * @param string $code Short link code.
     */
    public function qrPage(string $code): void
    {
        $link = Link::findByCode($code);
        if ($link === null) {
            http_response_code(404);
            view('errors/message', ['title' => __('error.link_not_found'), 'message' => __('error.code_not_found')]);
            return;
        }

        if ($link['status'] !== 'approved') {
            http_response_code(403);
            view('errors/message', ['title' => __('error.qr_unavailable'), 'message' => __('error.qr_after_approval')]);
            return;
        }

        view('public/qr', ['title' => $link['title'], 'link' => $link]);
    }

    /**
     * Streams the generated QR image as a downloadable PNG file.
     *
     * @param string $code Short link code.
     */
    public function downloadQr(string $code): void
    {
        $link = Link::findByCode($code);
        if ($link === null || empty($link['qr_path'])) {
            http_response_code(404);
            return;
        }

        $path = STORAGE_PATH . '/' . $link['qr_path'];
        if (!is_file($path)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_-]/', '_', $link['short_code']) . '.png"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
    }

    /**
     * Redirects an approved short code to its target URL and records a click.
     *
     * @param string $code Short link code.
     */
    public function redirect(string $code): void
    {
        $link = Link::findByCode($code);
        if ($link === null) {
            http_response_code(404);
            view('errors/message', ['title' => __('error.link_not_found'), 'message' => __('error.code_not_found')]);
            return;
        }

        if ($link['status'] !== 'approved') {
            http_response_code(403);
            $messages = [
                'pending' => [__('error.redirect_pending_title'), __('error.redirect_pending_message')],
                'rejected' => [__('error.redirect_rejected_title'), __('error.redirect_rejected_message')],
                'blocked' => [__('error.redirect_blocked_title'), __('error.redirect_blocked_message')],
            ];
            [$title, $message] = $messages[$link['status']] ?? [__('error.redirect_unavailable_title'), __('error.redirect_unavailable_message')];
            view('errors/message', ['title' => $title, 'message' => $message]);
            return;
        }

        Click::record(
            (int) $link['id'],
            ip_hash(),
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            null
        );

        header('Location: ' . $link['target_url'], true, 302);
        exit;
    }

    /**
     * Streams a stored QR PNG by filename.
     *
     * @param string $file QR image filename.
     */
    public function qrImage(string $file): void
    {
        if (!preg_match('/^[a-f0-9]{64}\.png$/', $file)) {
            http_response_code(404);
            return;
        }

        $path = STORAGE_PATH . '/qrcodes/' . $file;
        if (!is_file($path)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: image/png');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
    }

    /**
     * Generates a unique short code that passes validation.
     */
    private function generateCode(): string
    {
        do {
            $code = substr(strtr(base64_encode(random_bytes(8)), '+/', '*_'), 0, 8);
        } while (Validator::code($code) !== null || Link::codeExists($code));

        return $code;
    }

    /**
     * Renders the create form with a validation error and stops request handling.
     *
     * @param string $message Validation message.
     * @param array<string, mixed> $old Previously submitted form data.
     */
    private function validationError(string $message, array $old = []): never
    {
        http_response_code(422);
        view('public/create', [
            'title' => __('error.validation_title'),
            'error' => $message,
            'old' => $old,
        ]);
        exit;
    }
}

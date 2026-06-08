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

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends user and administrator notifications through SMTP or a local mail log.
 */
final class MailService
{
    /**
     * Sends a plain text message.
     */
    public function send(string $to, string $subject, string $body): void
    {
        $this->sendMessage($to, $subject, $body);
    }

    /**
     * Sends the initial notification after a link has been created.
     *
     * @param array<string, mixed> $link Link row.
     */
    public function sendLinkSubmitted(array $link): void
    {
        if (empty($link['submitter_email'])) {
            return;
        }

        $this->withLinkLocale($link, function () use ($link): void {
        $status = (string) ($link['status'] ?? 'pending');
        $subject = $status === 'approved' ? __('mail.submitted_approved_subject') : __('mail.submitted_subject');
        $lead = $status === 'approved'
            ? __('mail.submitted_approved_lead')
            : __('mail.submitted_lead');

        $this->sendLinkMail(
            (string) $link['submitter_email'],
            $subject,
            $lead,
            $link,
            $status === 'approved'
                ? [__('mail.button_open_short') => url((string) $link['short_code']), __('mail.button_download_qr') => url(localized_path('qr/' . $link['short_code'] . '/download'))]
                : [__('mail.button_download_qr') => url(localized_path('qr/' . $link['short_code'] . '/download'))],
            __('mail.auto_note')
        );
        });
    }

    /**
     * Sends a status change notification to the link submitter.
     *
     * @param array<string, mixed> $link Link row.
     */
    public function sendLinkStatusChanged(array $link, string $status): void
    {
        if (empty($link['submitter_email'])) {
            return;
        }

        $this->withLinkLocale($link, function () use ($link, $status): void {
        $subjects = [
            'pending' => __('mail.status_pending_subject'),
            'approved' => __('mail.status_approved_subject'),
            'rejected' => __('mail.status_rejected_subject'),
            'blocked' => __('mail.status_blocked_subject'),
        ];
        $leads = [
            'pending' => __('mail.status_pending_lead'),
            'approved' => __('mail.status_approved_lead'),
            'rejected' => __('mail.status_rejected_lead'),
            'blocked' => __('mail.status_blocked_lead'),
        ];

        $buttons = $status === 'approved'
            ? [__('mail.button_open_short') => url((string) $link['short_code']), __('mail.button_qr_page') => url(localized_path('qr/' . $link['short_code'])), __('mail.button_download_qr') => url(localized_path('qr/' . $link['short_code'] . '/download'))]
            : [__('mail.button_download_qr') => url(localized_path('qr/' . $link['short_code'] . '/download'))];

        $this->sendLinkMail(
            (string) $link['submitter_email'],
            $subjects[$status] ?? __('mail.status_pending_subject'),
            $leads[$status] ?? __('mail.status_pending_lead'),
            $link,
            $buttons
        );
        });
    }

    /**
     * Sends a notification after an administrator updates link details.
     *
     * @param array<string, mixed> $link Link row.
     */
    public function sendLinkUpdated(array $link): void
    {
        if (empty($link['submitter_email'])) {
            return;
        }

        $this->withLinkLocale($link, function () use ($link): void {
        $this->sendLinkMail(
            (string) $link['submitter_email'],
            __('mail.updated_subject'),
            __('mail.updated_lead'),
            $link,
            [__('mail.button_result') => url(localized_path('result/' . $link['short_code'])), __('mail.button_download_qr') => url(localized_path('qr/' . $link['short_code'] . '/download'))]
        );
        });
    }

    /**
     * Sends a notification after an administrator deletes a link.
     *
     * @param array<string, mixed> $link Link row.
     */
    public function sendLinkDeleted(array $link): void
    {
        if (empty($link['submitter_email'])) {
            return;
        }

        $this->withLinkLocale($link, function () use ($link): void {
        $this->sendLinkMail(
            (string) $link['submitter_email'],
            __('mail.deleted_subject'),
            __('mail.deleted_lead'),
            $link,
            [],
            __('mail.deleted_note'),
            false
        );
        });
    }

    /**
     * Sends a new-link notification to the configured administrator address.
     *
     * @param array<string, mixed> $link Link row.
     */
    public function sendAdminNewLink(array $link): void
    {
        $adminEmail = (string) config('mail.admin_to', '');
        if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->withLinkLocale($link, function () use ($link, $adminEmail): void {
        $this->sendLinkMail(
            $adminEmail,
            __('mail.admin_new_subject'),
            __('mail.admin_new_lead'),
            $link,
            [__('mail.button_admin') => url('admin/edit/' . $link['id']), __('mail.button_result') => url(localized_path('result/' . $link['short_code']))],
            __('mail.admin_note')
        );
        });
    }

    /**
     * Builds and sends an HTML link notification with optional embedded QR image.
     *
     * @param array<string, mixed> $link Link row.
     * @param array<string, string> $buttons Button label to URL map.
     */
    private function sendLinkMail(string $to, string $subject, string $lead, array $link, array $buttons = [], string $note = '', bool $includeDownload = true): void
    {
        $shortCode = (string) ($link['short_code'] ?? '');
        $status = (string) ($link['status'] ?? '');
        $rows = [
            __('mail.row_title') => (string) ($link['title'] ?? ''),
            __('mail.row_short_code') => $shortCode,
            __('mail.row_status') => $status,
            __('mail.row_result') => url(localized_path('result/' . $shortCode)),
        ];

        if ($status === 'approved') {
            $rows[__('mail.row_short_link')] = url($shortCode);
            $rows[__('mail.row_qr_page')] = url(localized_path('qr/' . $shortCode));
        }

        if ($includeDownload) {
            $rows[__('mail.row_download')] = url(localized_path('qr/' . $shortCode . '/download'));
        }

        if (!empty($link['target_url'])) {
            $rows[__('mail.row_original_url')] = (string) $link['target_url'];
        }

        $qrPath = !empty($link['qr_path']) ? STORAGE_PATH . '/' . $link['qr_path'] : null;
        $embedded = $qrPath !== null && is_file($qrPath) ? ['qr-code' => $qrPath] : [];
        $html = $this->renderHtml($subject, $lead, $rows, $buttons, $embedded !== [], $note);
        $text = $this->renderText($lead, $rows, $buttons, $note);

        $this->sendMessage($to, $subject, $text, $html, $embedded);
    }

    /**
     * Runs notification rendering in the link's stored locale.
     *
     * @param array<string, mixed> $link Link row.
     */
    private function withLinkLocale(array $link, callable $callback): void
    {
        $previous = app_locale();
        app_locale((string) ($link['locale'] ?? default_locale()), false);

        try {
            $callback();
        } finally {
            app_locale($previous, false);
        }
    }

    /**
     * Sends a message through SMTP or writes it to the mail log when SMTP is not configured.
     *
     * @param array<string, string> $embeddedImages CID to filesystem path map.
     */
    private function sendMessage(string $to, string $subject, string $body, ?string $html = null, array $embeddedImages = []): void
    {
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $smtp = config('mail.smtp', []);
        if (empty($smtp['host'])) {
            $this->log($to, $subject, $body);
            return;
        }

        try {
            $this->sendViaSmtp($to, $subject, $body, $smtp, $html, $embeddedImages);
        } catch (\Throwable $exception) {
            $this->log($to, $subject, $body);
            $this->logError($to, $subject, $exception);
        }
    }

    /**
     * Sends the message through PHPMailer.
     *
     * @param array<string, mixed> $smtp SMTP configuration.
     * @param array<string, string> $embeddedImages CID to filesystem path map.
     * @throws Exception
     */
    private function sendViaSmtp(string $to, string $subject, string $body, array $smtp, ?string $html = null, array $embeddedImages = []): void
    {
        $mail = new PHPMailer(true);
        $encryption = strtolower((string) ($smtp['encryption'] ?? 'tls'));

        $mail->isSMTP();
        $mail->Host = (string) ($smtp['host'] ?? '');
        $mail->Port = (int) ($smtp['port'] ?? 587);
        $mail->SMTPAuth = !empty($smtp['username']);
        $mail->Username = (string) ($smtp['username'] ?? '');
        $mail->Password = str_replace(' ', '', (string) ($smtp['password'] ?? ''));
        $mail->CharSet = 'UTF-8';

        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $from = config('mail.from', 'no-reply@example.com');
        $fromName = config('app.name', 'Q to me');

        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        foreach ($embeddedImages as $cid => $path) {
            if (is_string($cid) && is_string($path) && is_file($path)) {
                $mail->addEmbeddedImage($path, $cid, basename($path));
            }
        }
        if ($html !== null) {
            $mail->isHTML(true);
            $mail->Body = $html;
        } else {
            $mail->Body = $body;
        }
        $mail->AltBody = $body;
        $mail->send();
    }

    /**
     * Renders the HTML version of a notification e-mail.
     *
     * @param array<string, string> $rows Label to value map.
     * @param array<string, string> $buttons Button label to URL map.
     */
    private function renderHtml(string $title, string $lead, array $rows, array $buttons, bool $showQr, string $note): string
    {
        $rowsHtml = '';
        foreach ($rows as $label => $value) {
            $safeValue = filter_var($value, FILTER_VALIDATE_URL)
                ? '<a href="' . e($value) . '" style="color:#0f766e;text-decoration:none;">' . e($value) . '</a>'
                : e($value);
            $rowsHtml .= '<tr><td style="padding:9px 0;color:#63727d;width:150px;">' . e($label) . '</td><td style="padding:9px 0;font-weight:700;color:#18202a;">' . $safeValue . '</td></tr>';
        }

        $buttonsHtml = '';
        foreach ($buttons as $label => $href) {
            $buttonsHtml .= '<a href="' . e($href) . '" style="display:inline-block;margin:6px 8px 0 0;padding:11px 15px;border-radius:7px;background:#0f766e;color:#ffffff;text-decoration:none;font-weight:700;">' . e($label) . '</a>';
        }

        $qrHtml = $showQr
            ? '<div style="margin:22px 0;text-align:center;"><img src="cid:qr-code" alt="QR code" width="220" height="220" style="width:220px;height:220px;border:1px solid #d7e0e0;border-radius:8px;padding:12px;background:#ffffff;"></div>'
            : '';

        $noteHtml = $note !== '' ? '<p style="margin:22px 0 0;color:#63727d;font-size:13px;">' . e($note) . '</p>' : '';

        return '<!doctype html><html><body style="margin:0;padding:0;background:#eef3f2;font-family:Arial,Helvetica,sans-serif;color:#18202a;">'
            . '<div style="max-width:640px;margin:0 auto;padding:28px 14px;">'
            . '<div style="background:#ffffff;border:1px solid #d7e0e0;border-radius:8px;padding:28px;box-shadow:0 14px 34px rgba(17,45,53,.08);">'
            . '<div style="font-size:13px;font-weight:800;color:#0f766e;text-transform:uppercase;letter-spacing:.04em;">Q to me · q-2.me</div>'
            . '<h1 style="margin:10px 0 8px;font-size:26px;line-height:1.2;color:#112d35;">' . e($title) . '</h1>'
            . '<p style="margin:0 0 20px;font-size:16px;line-height:1.5;color:#374151;">' . e($lead) . '</p>'
            . $qrHtml
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #d7e0e0;border-bottom:1px solid #d7e0e0;margin:18px 0;border-collapse:collapse;">' . $rowsHtml . '</table>'
            . '<div>' . $buttonsHtml . '</div>'
            . $noteHtml
            . '</div></div></body></html>';
    }

    /**
     * Renders the plain text fallback for a notification e-mail.
     *
     * @param array<string, string> $rows Label to value map.
     * @param array<string, string> $buttons Button label to URL map.
     */
    private function renderText(string $lead, array $rows, array $buttons, string $note): string
    {
        $lines = [$lead, ''];
        foreach ($rows as $label => $value) {
            $lines[] = $label . ': ' . $value;
        }
        if ($buttons !== []) {
            $lines[] = '';
            foreach ($buttons as $label => $href) {
                $lines[] = $label . ': ' . $href;
            }
        }
        if ($note !== '') {
            $lines[] = '';
            $lines[] = $note;
        }
        return implode("\n", $lines);
    }

    /**
     * Writes a message body to the local mail log.
     */
    private function log(string $to, string $subject, string $body): void
    {
        $line = sprintf(
            "[%s]\nTo: %s\nSubject: %s\n%s\n\n",
            date('c'),
            $to,
            $subject,
            $body
        );
        file_put_contents(STORAGE_PATH . '/logs/mail.log', $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Writes SMTP error details to the local mail log.
     */
    private function logError(string $to, string $subject, \Throwable $exception): void
    {
        $line = sprintf(
            "[%s] SMTP error\nTo: %s\nSubject: %s\nError: %s\n\n",
            date('c'),
            $to,
            $subject,
            $exception->getMessage()
        );
        file_put_contents(STORAGE_PATH . '/logs/mail.log', $line, FILE_APPEND | LOCK_EX);
    }
}

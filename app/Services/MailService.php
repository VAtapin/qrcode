<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

final class MailService
{
    public function send(string $to, string $subject, string $body): void
    {
        $this->sendMessage($to, $subject, $body);
    }

    public function sendLinkSubmitted(array $link): void
    {
        if (empty($link['submitter_email'])) {
            return;
        }

        $status = (string) ($link['status'] ?? 'pending');
        $subject = $status === 'approved' ? 'QR-код одобрен' : 'QR-код ожидает модерации';
        $lead = $status === 'approved'
            ? 'Ссылка уже одобрена и короткий адрес работает.'
            : 'Заявка создана. Администратор проверит ссылку, после этого короткий адрес начнет работать.';

        $this->sendLinkMail(
            (string) $link['submitter_email'],
            $subject,
            $lead,
            $link,
            $status === 'approved'
                ? ['Открыть короткую ссылку' => url((string) $link['short_code']), 'Скачать QR' => url('qr/' . $link['short_code'] . '/download')]
                : ['Скачать QR' => url('qr/' . $link['short_code'] . '/download')],
            'Письмо создано автоматически сервисом Q to me.'
        );
    }

    public function sendLinkStatusChanged(array $link, string $status): void
    {
        if (empty($link['submitter_email'])) {
            return;
        }

        $subjects = [
            'pending' => 'QR-код снова ожидает модерации',
            'approved' => 'QR-код одобрен',
            'rejected' => 'QR-код отклонен',
            'blocked' => 'QR-код заблокирован',
        ];
        $leads = [
            'pending' => 'Ссылка возвращена на проверку. Короткий адрес временно недоступен.',
            'approved' => 'Ссылка одобрена. Короткий адрес и страница QR-кода уже работают.',
            'rejected' => 'Ссылка не прошла модерацию. Короткий адрес недоступен.',
            'blocked' => 'Ссылка заблокирована администратором. Переход по короткому адресу недоступен.',
        ];

        $buttons = $status === 'approved'
            ? ['Открыть короткую ссылку' => url((string) $link['short_code']), 'Страница QR' => url('qr/' . $link['short_code']), 'Скачать QR' => url('qr/' . $link['short_code'] . '/download')]
            : ['Скачать QR' => url('qr/' . $link['short_code'] . '/download')];

        $this->sendLinkMail(
            (string) $link['submitter_email'],
            $subjects[$status] ?? 'Статус QR-кода изменен',
            $leads[$status] ?? 'Статус ссылки изменен администратором.',
            $link,
            $buttons
        );
    }

    public function sendLinkUpdated(array $link): void
    {
        if (empty($link['submitter_email'])) {
            return;
        }

        $this->sendLinkMail(
            (string) $link['submitter_email'],
            'QR-код обновлен',
            'Администратор обновил данные вашей ссылки.',
            $link,
            ['Страница результата' => url('result/' . $link['short_code']), 'Скачать QR' => url('qr/' . $link['short_code'] . '/download')]
        );
    }

    public function sendLinkDeleted(array $link): void
    {
        if (empty($link['submitter_email'])) {
            return;
        }

        $this->sendLinkMail(
            (string) $link['submitter_email'],
            'QR-код удален',
            'Администратор удалил ссылку и QR-код из сервиса.',
            $link,
            [],
            'Если вы считаете, что это ошибка, ответьте на это письмо.',
            false
        );
    }

    public function sendAdminNewLink(array $link): void
    {
        $adminEmail = (string) config('mail.admin_to', '');
        if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->sendLinkMail(
            $adminEmail,
            'Создан новый QR-код',
            'В сервисе создана новая ссылка. Проверьте ее в админке.',
            $link,
            ['Открыть в админке' => url('admin/edit/' . $link['id']), 'Страница результата' => url('result/' . $link['short_code'])],
            'Это уведомление для администратора.'
        );
    }

    private function sendLinkMail(string $to, string $subject, string $lead, array $link, array $buttons = [], string $note = '', bool $includeDownload = true): void
    {
        $shortCode = (string) ($link['short_code'] ?? '');
        $status = (string) ($link['status'] ?? '');
        $rows = [
            'Название' => (string) ($link['title'] ?? ''),
            'Короткий код' => $shortCode,
            'Статус' => $status,
            'Страница результата' => url('result/' . $shortCode),
        ];

        if ($status === 'approved') {
            $rows['Короткая ссылка'] = url($shortCode);
            $rows['Страница QR'] = url('qr/' . $shortCode);
        }

        if ($includeDownload) {
            $rows['Скачать QR'] = url('qr/' . $shortCode . '/download');
        }

        if (!empty($link['target_url'])) {
            $rows['Оригинальный URL'] = (string) $link['target_url'];
        }

        $qrPath = !empty($link['qr_path']) ? STORAGE_PATH . '/' . $link['qr_path'] : null;
        $embedded = $qrPath !== null && is_file($qrPath) ? ['qr-code' => $qrPath] : [];
        $html = $this->renderHtml($subject, $lead, $rows, $buttons, $embedded !== [], $note);
        $text = $this->renderText($lead, $rows, $buttons, $note);

        $this->sendMessage($to, $subject, $text, $html, $embedded);
    }

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
            ? '<div style="margin:22px 0;text-align:center;"><img src="cid:qr-code" alt="QR-код" width="220" height="220" style="width:220px;height:220px;border:1px solid #d7e0e0;border-radius:8px;padding:12px;background:#ffffff;"></div>'
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

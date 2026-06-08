<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

final class MailService
{
    public function send(string $to, string $subject, string $body): void
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
            $this->sendViaSmtp($to, $subject, $body, $smtp);
        } catch (\Throwable $exception) {
            $this->log($to, $subject, $body);
            $this->logError($to, $subject, $exception);
        }
    }

    /**
     * @throws Exception
     */
    private function sendViaSmtp(string $to, string $subject, string $body, array $smtp): void
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
        $mail->Body = $body;
        $mail->AltBody = $body;
        $mail->send();
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

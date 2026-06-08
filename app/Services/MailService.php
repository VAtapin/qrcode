<?php

declare(strict_types=1);

namespace App\Services;

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

        if (!$this->sendViaSmtp($to, $subject, $body, $smtp)) {
            $this->log($to, $subject, $body);
        }
    }

    private function sendViaSmtp(string $to, string $subject, string $body, array $smtp): bool
    {
        $host = (string) ($smtp['host'] ?? '');
        $port = (int) ($smtp['port'] ?? 587);
        $encryption = strtolower((string) ($smtp['encryption'] ?? 'tls'));
        $target = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $socket = @stream_socket_client($target, $errno, $errstr, 10);
        if (!is_resource($socket)) {
            return false;
        }

        $ok = $this->expect($socket, [220])
            && $this->command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);

        if ($ok && $encryption === 'tls') {
            $ok = $this->command($socket, 'STARTTLS', [220])
                && @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)
                && $this->command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);
        }

        if ($ok && !empty($smtp['username'])) {
            $ok = $this->command($socket, 'AUTH LOGIN', [334])
                && $this->command($socket, base64_encode((string) $smtp['username']), [334])
                && $this->command($socket, base64_encode((string) $smtp['password']), [235]);
        }

        $from = config('mail.from', 'no-reply@example.com');
        $message = implode("\r\n", [
            'From: ' . $from,
            'To: ' . $to,
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'Content-Type: text/plain; charset=UTF-8',
            '',
            $body,
        ]);

        $ok = $ok
            && $this->command($socket, 'MAIL FROM:<' . $from . '>', [250])
            && $this->command($socket, 'RCPT TO:<' . $to . '>', [250, 251])
            && $this->command($socket, 'DATA', [354])
            && $this->command($socket, $message . "\r\n.", [250])
            && $this->command($socket, 'QUIT', [221]);

        fclose($socket);
        return $ok;
    }

    private function command(mixed $socket, string $command, array $codes): bool
    {
        fwrite($socket, $command . "\r\n");
        return $this->expect($socket, $codes);
    }

    private function expect(mixed $socket, array $codes): bool
    {
        $line = '';
        do {
            $line = fgets($socket, 515);
            if ($line === false) {
                return false;
            }
        } while (isset($line[3]) && $line[3] === '-');

        return in_array((int) substr($line, 0, 3), $codes, true);
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
}

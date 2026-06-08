<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Models\Click;
use App\Models\Link;
use App\Services\QrService;
use App\Services\RateLimiter;
use App\Services\Validator;

final class PublicController
{
    public function create(): void
    {
        view('public/create', ['title' => 'Создать короткую ссылку']);
    }

    public function store(): void
    {
        if (!Csrf::verify()) {
            $this->validationError('Сессия устарела. Обновите страницу и отправьте форму снова.');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $targetUrl = trim((string) ($_POST['target_url'] ?? ''));
        $shortCode = trim((string) ($_POST['short_code'] ?? ''));
        $qrColor = trim((string) ($_POST['qr_color'] ?? '#000000')) ?: '#000000';
        $comment = trim((string) ($_POST['comment'] ?? ''));
        $agree = isset($_POST['agree']);
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
        if ($agree === false) {
            $errors[] = 'Нужно согласиться с правилами сервиса.';
        }
        if (Link::codeExists($shortCode)) {
            $errors[] = 'Этот короткий код уже занят.';
        }
        if (RateLimiter::tooManySubmissions($ipHash)) {
            $errors[] = 'Слишком много заявок. Попробуйте позже.';
        }

        if ($errors !== []) {
            $this->validationError(implode(' ', $errors), $_POST);
        }

        $id = Link::create([
            'short_code' => $shortCode,
            'title' => $title,
            'target_url' => $targetUrl,
            'qr_color' => $qrColor,
            'comment' => $comment !== '' ? $comment : null,
            'created_ip_hash' => $ipHash,
        ]);

        $qrPath = (new QrService())->generate($shortCode, $qrColor);
        Link::updateQrPath($id, $qrPath);

        flash('success', 'Ссылка отправлена на проверку администратору');
        redirect('/');
    }

    public function redirect(string $code): void
    {
        $link = Link::findByCode($code);
        if ($link === null) {
            http_response_code(404);
            view('errors/message', ['title' => 'Ссылка не найдена', 'message' => 'Такого короткого кода нет.']);
            return;
        }

        if ($link['status'] !== 'approved') {
            http_response_code(403);
            $messages = [
                'pending' => ['Ссылка на модерации', 'Администратор еще проверяет эту ссылку.'],
                'rejected' => ['Ссылка отклонена', 'Эта ссылка не прошла модерацию.'],
                'blocked' => ['Ссылка заблокирована', 'Переход по этой ссылке заблокирован.'],
            ];
            [$title, $message] = $messages[$link['status']] ?? ['Переход недоступен', 'Ссылка сейчас недоступна.'];
            view('errors/message', ['title' => $title, 'message' => $message]);
            return;
        }

        Click::record(
            (int) $link['id'],
            ip_hash(),
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $_SERVER['HTTP_REFERER'] ?? null
        );

        header('Location: ' . $link['target_url'], true, 302);
        exit;
    }

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

    private function generateCode(): string
    {
        do {
            $code = substr(strtr(base64_encode(random_bytes(8)), '+/', '*_'), 0, 8);
        } while (Validator::code($code) !== null || Link::codeExists($code));

        return $code;
    }

    private function validationError(string $message, array $old = []): never
    {
        http_response_code(422);
        view('public/create', [
            'title' => 'Ошибка валидации',
            'error' => $message,
            'old' => $old,
        ]);
        exit;
    }
}

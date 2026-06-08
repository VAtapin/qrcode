<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Models\Click;
use App\Models\Link;
use App\Services\MailService;
use App\Services\QrService;
use App\Services\RateLimiter;
use App\Services\Validator;

final class PublicController
{
    public function gallery(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $filter = (string) ($_GET['filter'] ?? 'all');
        $filter = in_array($filter, ['all', 'public', 'latest'], true) ? $filter : 'all';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $gallery = Link::gallery($search, $filter, $page, $perPage);

        view('public/gallery', [
            'title' => 'Галерея QR-кодов',
            'items' => $gallery['items'],
            'total' => $gallery['total'],
            'page' => $gallery['page'],
            'pages' => $gallery['pages'],
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function create(): void
    {
        view('public/create', ['title' => 'Создать короткую ссылку']);
    }

    public function store(): void
    {
        if (!Csrf::verify()) {
            $this->validationError('Сессия устарела. Обновите страницу и отправьте форму снова.');
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
            $errors[] = 'Укажите корректный e-mail.';
        }
        if (!$agree) {
            $errors[] = 'Нужно согласиться с правилами сервиса.';
        }
        if (Link::codeExists($shortCode)) {
            $errors[] = 'Этот короткий код уже занят.';
        }
        if (!$isAdmin && RateLimiter::tooManySubmissions($ipHash)) {
            $errors[] = 'Слишком много заявок. Попробуйте позже.';
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
            'comment' => $comment !== '' ? $comment : null,
            'approved_at' => $approveNow ? date('Y-m-d H:i:s') : null,
            'created_ip_hash' => $ipHash,
        ]);

        $qrPath = (new QrService())->generate($shortCode, $qrColor);
        Link::updateQrPath($id, $qrPath);

        $link = Link::find($id);
        if ($link !== null && !empty($link['submitter_email'])) {
            $mailer = new MailService();
            if ($link['status'] === 'approved') {
                $mailer->send(
                    $link['submitter_email'],
                    'Ссылка одобрена',
                    "Ссылка одобрена.\nКороткая ссылка: " . url($link['short_code']) .
                    "\nСтраница QR-кода: " . url('qr/' . $link['short_code']) .
                    "\nСкачать QR: " . url('qr/' . $link['short_code'] . '/download')
                );
            } else {
                $mailer->send(
                    $link['submitter_email'],
                    'Ссылка ожидает модерации',
                    "Название: {$link['title']}\nКороткий код: {$link['short_code']}\nСтатус: {$link['status']}\nСсылка ожидает проверки администратором."
                );
            }
        }

        redirect('/result/' . $shortCode);
    }

    public function result(string $code): void
    {
        $link = Link::findByCode($code);
        if ($link === null) {
            http_response_code(404);
            view('errors/message', ['title' => 'Ссылка не найдена', 'message' => 'Такого короткого кода нет.']);
            return;
        }

        view('public/result', ['title' => 'QR-код создан', 'link' => $link]);
    }

    public function qrPage(string $code): void
    {
        $link = Link::findByCode($code);
        if ($link === null) {
            http_response_code(404);
            view('errors/message', ['title' => 'Ссылка не найдена', 'message' => 'Такого короткого кода нет.']);
            return;
        }

        if ($link['status'] !== 'approved') {
            http_response_code(403);
            view('errors/message', ['title' => 'QR-код недоступен', 'message' => 'QR-код будет доступен после одобрения ссылки.']);
            return;
        }

        view('public/qr', ['title' => $link['title'], 'link' => $link]);
    }

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
            null
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

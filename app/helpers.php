<?php

declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    static $config;
    $config ??= require dirname(__DIR__) . '/config/config.php';

    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(config('app.base_url', ''), '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function request_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function ip_hash(?string $ip = null): string
{
    return hash('sha256', ($ip ?? request_ip()) . config('app.secret_salt', ''));
}

function view(string $template, array $data = [], string $layout = 'layouts/main'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . '/app/Views/' . $template . '.php';
    $content = ob_get_clean();
    require dirname(__DIR__) . '/app/Views/' . $layout . '.php';
}

function flash(?string $key = null, ?string $message = null): mixed
{
    if ($key !== null && $message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    if ($key === null) {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

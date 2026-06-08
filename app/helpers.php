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

/**
 * Reads a nested configuration value using dot notation.
 */
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

/**
 * Escapes a value for safe HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Builds an absolute public URL from a path.
 */
function url(string $path = ''): string
{
    return rtrim(config('app.base_url', ''), '/') . '/' . ltrim($path, '/');
}

/**
 * Redirects the browser and stops request handling.
 */
function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

/**
 * Returns the current request IP address.
 */
function request_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Hashes an IP address with the application salt for privacy-preserving storage.
 */
function ip_hash(?string $ip = null): string
{
    return hash('sha256', ($ip ?? request_ip()) . config('app.secret_salt', ''));
}

/**
 * Renders a view inside a layout.
 *
 * @param array<string, mixed> $data Template variables.
 */
function view(string $template, array $data = [], string $layout = 'layouts/main'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . '/app/Views/' . $template . '.php';
    $content = ob_get_clean();
    require dirname(__DIR__) . '/app/Views/' . $layout . '.php';
}

/**
 * Stores or reads one-time flash messages in the session.
 */
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

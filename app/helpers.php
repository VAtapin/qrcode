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
 * Reads an editable application setting with config fallback.
 */
function setting(string $key, mixed $default = null): mixed
{
    static $settings;

    if ($settings === null) {
        try {
            $settings = \App\Models\AppSetting::all();
        } catch (\Throwable) {
            $settings = [];
        }
    }

    return array_key_exists($key, $settings) ? $settings[$key] : config($key, $default);
}

/**
 * Builds the fallback Impressum text for a locale.
 */
function legal_default_impressum_text(?string $locale = null): string
{
    $locale ??= app_locale();

    return __('legal.private_note', [], $locale);
}

/**
 * Builds the fallback privacy text for a locale.
 */
function legal_default_privacy_text(?string $locale = null): string
{
    $locale ??= app_locale();
    $email = (string) setting('legal.contact_email', 'atapin@gmail.com');

    return implode("\n\n", [
        __('privacy.purpose', [], $locale),
        __('privacy.logs', [], $locale),
        __('privacy.mail', [], $locale),
        __('privacy.cookies', [], $locale),
        __('privacy.rights', [], $locale),
        __('privacy.contact', ['email' => $email], $locale),
        __('privacy.implementation_note', ['maker' => 'Bible Media Agentur'], $locale),
        __('privacy.note', [], $locale),
    ]);
}

/**
 * Returns all supported interface locales.
 *
 * @return array<int, string>
 */
function supported_locales(): array
{
    return config('app.locales', ['de', 'ru', 'en']);
}

/**
 * Returns the configured default interface locale.
 */
function default_locale(): string
{
    return config('app.default_locale', 'de');
}

/**
 * Returns or sets the active interface locale.
 */
function app_locale(?string $locale = null, bool $persist = true): string
{
    static $current;

    if ($locale !== null) {
        $current = in_array($locale, supported_locales(), true) ? $locale : default_locale();
        $_SESSION['_locale'] = $current;
        if ($persist && !headers_sent()) {
            setcookie('locale', $current, [
                'expires' => time() + 31536000,
                'path' => '/',
                'secure' => !empty($_SERVER['HTTPS']),
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }
    }

    if ($current === null) {
        $current = $_SESSION['_locale'] ?? default_locale();
    }

    return $current;
}

/**
 * Detects the preferred locale from URL, cookie, browser headers, or default settings.
 */
function detect_locale(string $path): string
{
    $firstSegment = explode('/', trim($path, '/'))[0] ?? '';
    if (in_array($firstSegment, supported_locales(), true)) {
        return $firstSegment;
    }

    $cookieLocale = $_COOKIE['locale'] ?? '';
    if (is_string($cookieLocale) && in_array($cookieLocale, supported_locales(), true)) {
        return $cookieLocale;
    }

    $accepted = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    foreach (explode(',', $accepted) as $candidate) {
        $locale = substr(trim($candidate), 0, 2);
        if (in_array($locale, supported_locales(), true)) {
            return $locale;
        }
    }

    return default_locale();
}

/**
 * Translates an interface string.
 *
 * @param array<string, string|int|float> $replace Placeholder replacements.
 */
function __(string $key, array $replace = [], ?string $locale = null): string
{
    static $catalogs = [];

    $locale ??= app_locale();
    if (!isset($catalogs[$locale])) {
        $file = dirname(__DIR__) . '/app/Lang/' . $locale . '.php';
        $catalogs[$locale] = is_file($file) ? require $file : [];
    }

    $defaultLocale = default_locale();
    if (!isset($catalogs[$defaultLocale])) {
        $file = dirname(__DIR__) . '/app/Lang/' . $defaultLocale . '.php';
        $catalogs[$defaultLocale] = is_file($file) ? require $file : [];
    }

    $line = $catalogs[$locale][$key] ?? $catalogs[$defaultLocale][$key] ?? $key;
    foreach ($replace as $name => $value) {
        $line = str_replace(':' . $name, (string) $value, $line);
    }

    return $line;
}

/**
 * Builds a localized public path.
 */
function localized_path(string $path = '', ?string $locale = null): string
{
    $locale ??= app_locale();
    $path = trim($path, '/');

    return '/' . $locale . ($path !== '' ? '/' . $path : '');
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

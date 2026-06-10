<?php
$pageTitle = (string) ($title ?? config('app.name'));
$metaDescription = __('meta.description');
$canonicalUrl = url(ltrim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), '/'));
$socialImageUrl = url('assets/social-preview.png');
?>
<!doctype html>
<html lang="<?= e(app_locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Q to me">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:image" content="<?= e($socialImageUrl) ?>">
    <meta property="og:image:secure_url" content="<?= e($socialImageUrl) ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Q to me - q-2.me">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">
    <meta name="twitter:image" content="<?= e($socialImageUrl) ?>">
    <link rel="icon" href="/assets/logo.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="/">
        <img src="/assets/logo.svg" alt="" class="brand-logo" width="44" height="44">
        <span class="brand-copy">
            <span>Q to me</span>
            <small><?= e(__('brand.tagline')) ?></small>
        </span>
    </a>
    <nav>
        <a href="<?= e(localized_path()) ?>"><?= e(__('nav.gallery')) ?></a>
        <a href="<?= e(localized_path('new')) ?>"><?= e(__('nav.new_link')) ?></a>
        <?php if (!empty($_SESSION['admin_id'])): ?>
            <a href="/admin"><?= e(__('nav.admin')) ?></a>
            <a href="/admin/blacklist"><?= e(__('nav.blacklist')) ?></a>
            <a href="/admin/settings"><?= e(__('nav.settings')) ?></a>
            <form method="post" action="/logout" class="inline">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit"><?= e(__('nav.logout')) ?></button>
            </form>
        <?php else: ?>
            <a href="/login"><?= e(__('nav.login')) ?></a>
        <?php endif; ?>
        <details class="language-picker">
            <summary aria-label="Language">
                <span class="language-current">Language: <?= e(strtoupper(app_locale())) ?></span>
            </summary>
            <span class="language-options">
                <?php foreach (supported_locales() as $locale): ?>
                    <?php if (app_locale() !== $locale): ?>
                        <a href="<?= e(locale_switch_url($locale)) ?>"><?= e(strtoupper($locale)) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </span>
        </details>
    </nav>
</header>
<main class="container">
    <?php foreach (flash() as $kind => $message): ?>
        <div class="alert <?= e($kind) ?>"><?= e($message) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
<footer class="site-footer">
    <span>&copy; <?= date('Y') ?> Q to me</span>
    <span><?= e(__('footer.maker')) ?>: <a href="https://bible-media.de/" target="_blank" rel="noreferrer">Bible Media Agentur</a></span>
    <span><a href="<?= e(localized_path('impressum')) ?>">Impressum</a> | <a href="<?= e(localized_path('datenschutz')) ?>"><?= e(__('privacy.title')) ?></a></span>
</footer>
</body>
</html>

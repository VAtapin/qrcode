<!doctype html>
<html lang="<?= e(app_locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? config('app.name')) ?></title>
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
        <span class="language-picker" aria-label="Language">
            <span class="language-current">🌐 <?= e(strtoupper(app_locale())) ?></span>
            <span class="language-options">
            <?php foreach (supported_locales() as $locale): ?>
                <?php if (app_locale() !== $locale): ?>
                    <a href="<?= e(locale_switch_url($locale)) ?>"><?= e(strtoupper($locale)) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            </span>
        </span>
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
    <span><a href="<?= e(localized_path('impressum')) ?>">Impressum</a> · <a href="<?= e(localized_path('datenschutz')) ?>"><?= e(__('privacy.title')) ?></a></span>
</footer>
</body>
</html>

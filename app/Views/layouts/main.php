<!doctype html>
<html lang="ru">
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
            <small>QR ко мне · q-2.me</small>
        </span>
    </a>
    <nav>
        <a href="/">Галерея</a>
        <a href="/new">Новая ссылка</a>
        <?php if (!empty($_SESSION['admin_id'])): ?>
            <a href="/admin">Админка</a>
            <a href="/admin/blacklist">Чёрный список</a>
            <form method="post" action="/logout" class="inline">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit">Выйти</button>
            </form>
        <?php else: ?>
            <a href="/login">Войти</a>
        <?php endif; ?>
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
    <span>Разработка и поддержка: <a href="https://bible-media.de/" target="_blank" rel="noreferrer">Bible Media Agentur</a></span>
    <span><a href="/impressum">Impressum</a> · <a href="/datenschutz">Datenschutz</a></span>
</footer>
</body>
</html>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? config('app.name')) ?></title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="/">QR Moderation</a>
    <nav>
        <a href="/">Новая ссылка</a>
        <?php if (!empty($_SESSION['admin_id'])): ?>
            <a href="/admin">Админка</a>
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
</body>
</html>

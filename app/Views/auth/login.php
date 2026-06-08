<section class="panel narrow">
    <h1>Вход администратора</h1>
    <?php if ($message = flash('error')): ?>
        <div class="alert error"><?= e($message) ?></div>
    <?php endif; ?>
    <form method="post" action="/login" class="form">
        <?= \App\Core\Csrf::field() ?>
        <label>
            Логин
            <input name="login" autocomplete="username" required>
        </label>
        <label>
            Пароль
            <input name="password" type="password" autocomplete="current-password" required>
        </label>
        <button class="primary" type="submit">Войти</button>
    </form>
</section>

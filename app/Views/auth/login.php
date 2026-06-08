<section class="panel narrow">
    <h1><?= e(__('auth.login_title')) ?></h1>
    <?php if ($message = flash('error')): ?>
        <div class="alert error"><?= e($message) ?></div>
    <?php endif; ?>
    <form method="post" action="/login" class="form">
        <?= \App\Core\Csrf::field() ?>
        <label>
            <?= e(__('auth.login')) ?>
            <input name="login" autocomplete="username" required>
        </label>
        <label>
            <?= e(__('auth.password')) ?>
            <input name="password" type="password" autocomplete="current-password" required>
        </label>
        <button class="primary" type="submit"><?= e(__('button.sign_in')) ?></button>
    </form>
</section>

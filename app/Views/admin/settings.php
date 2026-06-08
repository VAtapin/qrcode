<section class="panel narrow">
    <h1><?= e(__('admin.settings')) ?></h1>
    <p class="muted"><?= e(__('admin.settings_hint')) ?></p>

    <?php if ($message = flash('error')): ?><div class="alert error"><?= e($message) ?></div><?php endif; ?>
    <?php if ($message = flash('success')): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>

    <form method="post" action="/admin/settings" class="form">
        <?= \App\Core\Csrf::field() ?>
        <label>
            <?= e(__('admin.language')) ?>
            <select name="locale" required>
                <?php foreach (supported_locales() as $locale): ?>
                    <option value="<?= e($locale) ?>" <?= ($admin['locale'] ?? default_locale()) === $locale ? 'selected' : '' ?>>
                        <?= e(strtoupper($locale)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="primary" type="submit"><?= e(__('button.save')) ?></button>
    </form>
</section>

<section class="panel narrow">
    <h1><?= e(__('blacklist.title')) ?></h1>
    <?php if ($message = flash('error')): ?><div class="alert error"><?= e($message) ?></div><?php endif; ?>
    <form method="post" action="/admin/blacklist" class="form compact-form">
        <?= \App\Core\Csrf::field() ?>
        <label>
            <?= e(__('blacklist.word')) ?>
            <input name="word" required minlength="3" maxlength="50" pattern="[A-Za-z0-9*_]{3,50}">
        </label>
        <button class="primary" type="submit"><?= e(__('blacklist.add')) ?></button>
    </form>

    <div class="word-list">
        <?php foreach ($words as $word): ?>
            <form method="post" action="/admin/blacklist/delete/<?= e((string) $word['id']) ?>" class="word-row">
                <?= \App\Core\Csrf::field() ?>
                <code><?= e($word['word']) ?></code>
                <button type="submit"><?= e(__('button.delete')) ?></button>
            </form>
        <?php endforeach; ?>
        <?php if ($words === []): ?>
            <p class="muted"><?= e(__('blacklist.empty')) ?></p>
        <?php endif; ?>
    </div>
</section>

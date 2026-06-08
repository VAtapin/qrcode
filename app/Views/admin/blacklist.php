<section class="panel narrow">
    <h1>Чёрный список кодов</h1>
    <?php if ($message = flash('error')): ?><div class="alert error"><?= e($message) ?></div><?php endif; ?>
    <form method="post" action="/admin/blacklist" class="form compact-form">
        <?= \App\Core\Csrf::field() ?>
        <label>
            Запрещённое слово
            <input name="word" required minlength="3" maxlength="50" pattern="[A-Za-z0-9*_]{3,50}">
        </label>
        <button class="primary" type="submit">Добавить</button>
    </form>

    <div class="word-list">
        <?php foreach ($words as $word): ?>
            <form method="post" action="/admin/blacklist/delete/<?= e((string) $word['id']) ?>" class="word-row">
                <?= \App\Core\Csrf::field() ?>
                <code><?= e($word['word']) ?></code>
                <button type="submit">Удалить</button>
            </form>
        <?php endforeach; ?>
        <?php if ($words === []): ?>
            <p class="muted">Список пуст.</p>
        <?php endif; ?>
    </div>
</section>

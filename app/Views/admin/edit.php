<section class="panel">
    <h1>Редактирование ссылки #<?= e((string) $link['id']) ?></h1>
    <?php if ($message = flash('error')): ?><div class="alert error"><?= e($message) ?></div><?php endif; ?>
    <form method="post" action="/admin/edit/<?= e((string) $link['id']) ?>" class="form">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="return_to" value="/admin/edit/<?= e((string) $link['id']) ?>">
        <label>Название<input name="title" required value="<?= e($link['title']) ?>"></label>
        <label>URL<input name="target_url" type="url" required value="<?= e($link['target_url']) ?>"></label>
        <label>Короткий код<input name="short_code" required value="<?= e($link['short_code']) ?>"></label>
        <label>E-mail автора<input name="submitter_email" type="email" value="<?= e($link['submitter_email']) ?>"></label>
        <label>Цвет QR<input name="qr_color" type="color" value="<?= e($link['qr_color']) ?>"></label>
        <label class="check">
            <input type="checkbox" name="is_public" value="1" <?= (int) $link['is_public'] === 1 ? 'checked' : '' ?>>
            <span>Показывать в публичной галерее</span>
        </label>
        <label>Комментарий<textarea name="comment" rows="3"><?= e($link['comment']) ?></textarea></label>
        <label>Заметка администратора<textarea name="admin_note" rows="3"><?= e($link['admin_note']) ?></textarea></label>
        <button class="primary" type="submit">Сохранить</button>
    </form>
    <div class="actions edit-actions">
        <a class="button" href="/qr/<?= e($link['short_code']) ?>">Открыть страницу QR</a>
        <a class="button" href="/qr/<?= e($link['short_code']) ?>/download">Скачать QR</a>
    </div>
    <form method="post" action="/admin/delete/<?= e((string) $link['id']) ?>" class="danger-form">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="return_to" value="/admin/<?= e($link['status']) ?>">
        <button type="submit" class="danger">Удалить запись и QR-код</button>
    </form>
</section>

<section class="panel">
    <h1>Редактирование ссылки #<?= e((string) $link['id']) ?></h1>
    <?php if ($message = flash('error')): ?><div class="alert error"><?= e($message) ?></div><?php endif; ?>
    <form method="post" action="/admin/edit/<?= e((string) $link['id']) ?>" class="form">
        <?= \App\Core\Csrf::field() ?>
        <label>Название<input name="title" required value="<?= e($link['title']) ?>"></label>
        <label>URL<input name="target_url" type="url" required value="<?= e($link['target_url']) ?>"></label>
        <label>Короткий код<input name="short_code" required value="<?= e($link['short_code']) ?>"></label>
        <label>Цвет QR<input name="qr_color" type="color" value="<?= e($link['qr_color']) ?>"></label>
        <label>Комментарий<textarea name="comment" rows="3"><?= e($link['comment']) ?></textarea></label>
        <label>Заметка администратора<textarea name="admin_note" rows="3"><?= e($link['admin_note']) ?></textarea></label>
        <button class="primary" type="submit">Сохранить</button>
    </form>
    <form method="post" action="/admin/delete/<?= e((string) $link['id']) ?>" class="danger-form">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit" class="danger">Удалить запись</button>
    </form>
</section>

<section class="panel">
    <h1><?= e(__('admin.editing', ['id' => (string) $link['id']])) ?></h1>
    <?php if ($message = flash('error')): ?><div class="alert error"><?= e($message) ?></div><?php endif; ?>
    <form method="post" action="/admin/edit/<?= e((string) $link['id']) ?>" class="form">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="return_to" value="/admin/edit/<?= e((string) $link['id']) ?>">
        <label><?= e(__('admin.title')) ?><input name="title" required value="<?= e($link['title']) ?>"></label>
        <label>URL<input name="target_url" type="url" required value="<?= e($link['target_url']) ?>"></label>
        <label><?= e(__('admin.short_code')) ?><input name="short_code" required value="<?= e($link['short_code']) ?>"></label>
        <label><?= e(__('admin.author_email')) ?><input name="submitter_email" type="email" value="<?= e($link['submitter_email']) ?>"></label>
        <label><?= e(__('admin.qr_color')) ?><input name="qr_color" type="color" value="<?= e($link['qr_color']) ?>"></label>
        <label class="check">
            <input type="checkbox" name="is_public" value="1" <?= (int) $link['is_public'] === 1 ? 'checked' : '' ?>>
            <span><?= e(__('form.is_public')) ?></span>
        </label>
        <label><?= e(__('admin.comment')) ?><textarea name="comment" rows="3"><?= e($link['comment']) ?></textarea></label>
        <label><?= e(__('admin.note')) ?><textarea name="admin_note" rows="3"><?= e($link['admin_note']) ?></textarea></label>
        <button class="primary" type="submit"><?= e(__('button.save')) ?></button>
    </form>
    <div class="actions edit-actions">
        <a class="button" href="<?= e(localized_path('qr/' . $link['short_code'])) ?>"><?= e(__('button.open_qr_page')) ?></a>
        <a class="button" href="<?= e(localized_path('qr/' . $link['short_code'] . '/download')) ?>"><?= e(__('button.download_qr')) ?></a>
    </div>
    <form method="post" action="/admin/delete/<?= e((string) $link['id']) ?>" class="danger-form">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="return_to" value="/admin/<?= e($link['status']) ?>">
        <button type="submit" class="danger"><?= e(__('button.delete_record_qr')) ?></button>
    </form>
</section>

<section class="panel wide">
    <div class="heading-row">
        <h1><?= e($title) ?></h1>
        <a href="/admin"><?= e(__('admin.to_stats')) ?></a>
    </div>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
            <tr>
                <th>ID</th>
                <th><?= e(__('admin.code')) ?></th>
                <th><?= e(__('admin.title')) ?></th>
                <th><?= e(__('admin.url')) ?></th>
                <th><?= e(__('admin.qr')) ?></th>
                <th><?= e(__('admin.clicks')) ?></th>
                <th><?= e(__('admin.actions')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($links as $link): ?>
                <tr>
                    <td><?= e((string) $link['id']) ?></td>
                    <td><code><?= e($link['short_code']) ?></code></td>
                    <td class="wrap-cell">
                        <?= e($link['title']) ?>
                        <?php if ((int) $link['duplicates'] > 0): ?>
                            <span class="badge"><?= e(__('admin.duplicate_url')) ?></span>
                        <?php endif; ?>
                        <?php if ((int) $link['is_public'] === 1): ?>
                            <span class="badge green"><?= e(__('gallery.badge_public')) ?></span>
                        <?php else: ?>
                            <span class="badge"><?= e(__('gallery.badge_private')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="url-cell">
                        <a href="<?= e($link['target_url']) ?>" rel="noreferrer" target="_blank"><?= e($link['target_url']) ?></a>
                    </td>
                    <td><?php if (!empty($link['qr_path'])): ?><img class="qr" src="/storage/<?= e($link['qr_path']) ?>" alt="QR"><?php endif; ?></td>
                    <td>
                        <?= e((string) $link['click_count']) ?><br>
                        <span class="muted"><?= e($link['last_clicked_at'] ?: __('admin.no_clicks')) ?></span>
                    </td>
                    <td class="actions">
                        <a href="/admin/edit/<?= e((string) $link['id']) ?>"><?= e(__('button.edit')) ?></a>
                        <a href="<?= e(localized_path('qr/' . $link['short_code'])) ?>"><?= e(__('button.open_qr_page')) ?></a>
                        <a href="<?= e(localized_path('qr/' . $link['short_code'] . '/download')) ?>"><?= e(__('button.download_qr')) ?></a>
                        <?php foreach (['approved' => __('admin.approve'), 'rejected' => __('admin.reject'), 'blocked' => __('admin.block')] as $next => $label): ?>
                            <?php if ($status !== $next): ?>
                                <form method="post" action="/admin/status/<?= e((string) $link['id']) ?>">
                                    <?= \App\Core\Csrf::field() ?>
                                    <input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? ('/admin/' . $status)) ?>">
                                    <input type="hidden" name="status" value="<?= e($next) ?>">
                                    <button type="submit"><?= e($label) ?></button>
                                </form>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <form method="post" action="/admin/delete/<?= e((string) $link['id']) ?>" onsubmit="return confirm('<?= e(__('admin.delete_confirm')) ?>')">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? ('/admin/' . $status)) ?>">
                            <button type="submit" class="danger"><?= e(__('button.delete')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($links === []): ?>
                <tr><td colspan="7" class="empty"><?= e(__('admin.no_records')) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

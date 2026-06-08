<section class="panel wide">
    <div class="heading-row">
        <h1><?= e($title) ?></h1>
        <a href="/admin">К статистике</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Код</th>
                <th>Название</th>
                <th>URL</th>
                <th>QR</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($links as $link): ?>
                <tr>
                    <td><?= e((string) $link['id']) ?></td>
                    <td><code><?= e($link['short_code']) ?></code></td>
                    <td>
                        <?= e($link['title']) ?>
                        <?php if ((int) $link['duplicates'] > 0): ?>
                            <span class="badge">дубликат URL</span>
                        <?php endif; ?>
                    </td>
                    <td><a href="<?= e($link['target_url']) ?>" rel="noreferrer" target="_blank"><?= e($link['target_url']) ?></a></td>
                    <td><?php if (!empty($link['qr_path'])): ?><img class="qr" src="/storage/<?= e($link['qr_path']) ?>" alt="QR"><?php endif; ?></td>
                    <td class="actions">
                        <a href="/admin/edit/<?= e((string) $link['id']) ?>">Редактировать</a>
                        <?php foreach (['approved' => 'Одобрить', 'rejected' => 'Отклонить', 'blocked' => 'Блокировать'] as $next => $label): ?>
                            <?php if ($status !== $next): ?>
                                <form method="post" action="/admin/status/<?= e((string) $link['id']) ?>">
                                    <?= \App\Core\Csrf::field() ?>
                                    <input type="hidden" name="status" value="<?= e($next) ?>">
                                    <button type="submit"><?= e($label) ?></button>
                                </form>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($links === []): ?>
                <tr><td colspan="6" class="empty">Записей нет</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

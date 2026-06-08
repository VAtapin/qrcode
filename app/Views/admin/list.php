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
                <th>Переходы</th>
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
                        <?php if ((int) $link['is_public'] === 1): ?>
                            <span class="badge green">публичная</span>
                        <?php endif; ?>
                    </td>
                    <td><a href="<?= e($link['target_url']) ?>" rel="noreferrer" target="_blank"><?= e($link['target_url']) ?></a></td>
                    <td><?php if (!empty($link['qr_path'])): ?><img class="qr" src="/storage/<?= e($link['qr_path']) ?>" alt="QR"><?php endif; ?></td>
                    <td>
                        <?= e((string) $link['click_count']) ?><br>
                        <span class="muted"><?= e($link['last_clicked_at'] ?: 'нет переходов') ?></span>
                    </td>
                    <td class="actions">
                        <a href="/admin/edit/<?= e((string) $link['id']) ?>">Редактировать</a>
                        <a href="/qr/<?= e($link['short_code']) ?>">Открыть страницу QR</a>
                        <a href="/qr/<?= e($link['short_code']) ?>/download">Скачать QR</a>
                        <?php foreach (['approved' => 'Одобрить', 'rejected' => 'Отклонить', 'blocked' => 'Блокировать'] as $next => $label): ?>
                            <?php if ($status !== $next): ?>
                                <form method="post" action="/admin/status/<?= e((string) $link['id']) ?>">
                                    <?= \App\Core\Csrf::field() ?>
                                    <input type="hidden" name="status" value="<?= e($next) ?>">
                                    <button type="submit"><?= e($label) ?></button>
                                </form>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <form method="post" action="/admin/delete/<?= e((string) $link['id']) ?>" onsubmit="return confirm('Удалить запись и QR-код?')">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="danger">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($links === []): ?>
                <tr><td colspan="7" class="empty">Записей нет</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

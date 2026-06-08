<section class="panel">
    <h1>Создать короткую ссылку</h1>
    <p class="muted">QR-код можно скачать сразу, а переход по ссылке заработает после одобрения.</p>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/links" class="form">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="form_started_at" value="<?= time() ?>">
        <label class="hp-field" aria-hidden="true" tabindex="-1">
            Website
            <input name="website" autocomplete="off" tabindex="-1">
        </label>
        <label>
            Название ссылки
            <input name="title" maxlength="190" required value="<?= e($old['title'] ?? '') ?>">
        </label>
        <label>
            Оригинальный URL
            <input name="target_url" type="url" required placeholder="https://example.com" value="<?= e($old['target_url'] ?? '') ?>">
        </label>
        <label>
            Желаемый короткий код
            <input name="short_code" minlength="3" maxlength="50" pattern="[A-Za-z0-9*_]{3,50}" value="<?= e($old['short_code'] ?? '') ?>">
        </label>
        <label>
            <?= empty($_SESSION['admin_id']) ? 'E-mail' : 'E-mail автора' ?>
            <input name="submitter_email" type="email" <?= empty($_SESSION['admin_id']) ? 'required' : '' ?> value="<?= e($old['submitter_email'] ?? '') ?>">
        </label>
        <label>
            Цвет QR-кода
            <input name="qr_color" type="color" value="<?= e($old['qr_color'] ?? '#000000') ?>">
        </label>
        <label>
            Комментарий
            <textarea name="comment" rows="4"><?= e($old['comment'] ?? '') ?></textarea>
        </label>
        <label class="check">
            <input type="checkbox" name="is_public" value="1" <?= !empty($old['is_public']) ? 'checked' : '' ?>>
            <span>Показывать в публичной галерее</span>
        </label>
        <?php if (!empty($_SESSION['admin_id'])): ?>
            <label class="check">
                <input type="checkbox" name="approve_now" value="1">
                <span>Сразу одобрить</span>
            </label>
        <?php endif; ?>
        <label class="check">
            <input type="checkbox" name="agree" value="1" required>
            <span>Согласен с правилами сервиса</span>
        </label>
        <button class="primary" type="submit">Создать QR-код</button>
    </form>
</section>

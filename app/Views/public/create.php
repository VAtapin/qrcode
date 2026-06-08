<section class="panel">
    <h1>Создать короткую ссылку</h1>
    <p class="muted">Ссылка и QR-код станут рабочими после проверки администратором.</p>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/links" class="form">
        <?= \App\Core\Csrf::field() ?>
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
            Цвет QR-кода
            <input name="qr_color" type="color" value="<?= e($old['qr_color'] ?? '#000000') ?>">
        </label>
        <label>
            Комментарий
            <textarea name="comment" rows="4"><?= e($old['comment'] ?? '') ?></textarea>
        </label>
        <label class="check">
            <input type="checkbox" name="agree" value="1" required>
            <span>Согласен с правилами сервиса</span>
        </label>
        <button class="primary" type="submit">Отправить на проверку</button>
    </form>
</section>

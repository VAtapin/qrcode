<section class="panel narrow">
    <h1>QR-код создан</h1>
    <p class="muted">Ссылка отправлена на модерацию. QR-код уже можно скачать.</p>
    <div class="result-box">
        <h2><?= e($link['title']) ?></h2>
        <p><strong>Короткий код:</strong> <code><?= e($link['short_code']) ?></code></p>
        <p><strong>Статус:</strong> <?= e($link['status']) ?></p>
        <?php if (!empty($link['qr_path'])): ?>
            <img class="qr-large" src="/storage/<?= e($link['qr_path']) ?>" alt="QR">
        <?php endif; ?>
        <div class="actions">
            <a class="button" href="/qr/<?= e($link['short_code']) ?>">Страница QR</a>
            <a class="button primary" href="/qr/<?= e($link['short_code']) ?>/download">Скачать QR</a>
        </div>
    </div>
</section>

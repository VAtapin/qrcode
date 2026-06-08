<section class="panel narrow center">
    <h1><?= e($link['title']) ?></h1>
    <?php if (!empty($link['qr_path'])): ?>
        <img class="qr-xlarge" src="/storage/<?= e($link['qr_path']) ?>" alt="QR">
    <?php endif; ?>
    <p><code><?= e(url($link['short_code'])) ?></code></p>
    <div class="actions center-actions">
        <a class="button" href="/<?= e($link['short_code']) ?>" target="_blank" rel="noreferrer">Открыть</a>
        <a class="button primary" href="/qr/<?= e($link['short_code']) ?>/download">Скачать QR</a>
    </div>
</section>

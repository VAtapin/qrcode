<section class="panel narrow center">
    <h1><?= e($link['title']) ?></h1>
    <?php if (!empty($link['qr_path'])): ?>
        <img class="qr-xlarge" src="/storage/<?= e($link['qr_path']) ?>" alt="QR">
    <?php endif; ?>
    <p><code><?= e(url($link['short_code'])) ?></code></p>
    <div class="actions center-actions">
        <a class="button" href="/<?= e($link['short_code']) ?>" target="_blank" rel="noreferrer"><?= e(__('button.open')) ?></a>
        <a class="button primary" href="<?= e(localized_path('qr/' . $link['short_code'] . '/download')) ?>"><?= e(__('button.download_qr')) ?></a>
    </div>
</section>

<section class="panel narrow">
    <h1><?= e(__('result.title')) ?></h1>
    <p class="muted"><?= e(__('result.subtitle')) ?></p>
    <div class="result-box">
        <h2><?= e($link['title']) ?></h2>
        <p><strong><?= e(__('label.short_code')) ?>:</strong> <code><?= e($link['short_code']) ?></code></p>
        <p><strong><?= e(__('label.status')) ?>:</strong> <?= e($link['status']) ?></p>
        <?php if (!empty($link['qr_path'])): ?>
            <img class="qr-large" src="/storage/<?= e($link['qr_path']) ?>" alt="QR">
        <?php endif; ?>
        <div class="actions">
            <a class="button" href="<?= e(localized_path('qr/' . $link['short_code'])) ?>"><?= e(__('button.qr_page')) ?></a>
            <a class="button primary" href="<?= e(localized_path('qr/' . $link['short_code'] . '/download')) ?>"><?= e(__('button.download_qr')) ?></a>
        </div>
    </div>
</section>

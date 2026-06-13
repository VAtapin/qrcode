<?php
$agbText = trim((string) setting('legal.agb.' . app_locale(), ''));
?>
<section class="panel narrow">
    <h1><?= e(__('agb.title')) ?></h1>
    <p class="muted"><?= e(__('agb.subtitle')) ?></p>

    <?php if ($agbText !== ''): ?>
        <div class="legal-text"><?= nl2br(e($agbText)) ?></div>
    <?php else: ?>
        <p class="legal-text"><?= e(__('agb.empty')) ?></p>
    <?php endif; ?>
</section>

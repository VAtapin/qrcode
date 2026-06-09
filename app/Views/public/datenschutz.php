<?php
$contactEmail = (string) setting('legal.contact_email', 'atapin@gmail.com');
$phone = trim((string) setting('legal.phone', legal_default_phone()));
$address = trim((string) setting('legal.impressum_address', legal_default_impressum_address()));
$privacyText = trim((string) setting('legal.privacy_text.' . app_locale(), legal_default_privacy_text()));
if ($privacyText === '') {
    $privacyText = legal_default_privacy_text();
}
?>
<section class="panel narrow">
    <h1><?= e(__('privacy.title')) ?></h1>
    <p class="muted"><?= e(__('privacy.subtitle')) ?></p>

    <h2><?= e(__('privacy.controller')) ?></h2>
    <p>
        <?= nl2br(e(legal_default_provider())) ?><br>
        <?= nl2br(e($address)) ?><br>
        <?php if ($phone !== ''): ?><?= e(__('legal.phone')) ?>: <?= e($phone) ?><br><?php endif; ?>
        <?= e(__('legal.email')) ?>: <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
    </p>

    <div class="legal-text"><?= nl2br(e($privacyText)) ?></div>
</section>

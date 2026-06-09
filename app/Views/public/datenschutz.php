<?php
$contactEmail = (string) setting('legal.contact_email', legal_default_contact_email());
$phone = trim((string) setting('legal.phone', legal_default_phone()));
$address = trim((string) setting('legal.impressum_address', legal_default_impressum_address()));
$provider = trim((string) setting('legal.provider', legal_default_provider()));
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
        <?php if ($provider !== ''): ?><?= nl2br(e($provider)) ?><br><?php endif; ?>
        <?php if ($address !== ''): ?><?= nl2br(e($address)) ?><br><?php endif; ?>
        <?php if ($phone !== ''): ?><?= e(__('legal.phone')) ?>: <?= e($phone) ?><br><?php endif; ?>
        <?php if ($contactEmail !== ''): ?><?= e(__('legal.email')) ?>: <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a><?php endif; ?>
    </p>

    <div class="legal-text"><?= nl2br(e($privacyText)) ?></div>
</section>

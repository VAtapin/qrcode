<?php
$contactEmail = (string) setting('legal.contact_email', 'atapin@gmail.com');
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
        Volodymyr Atapin<br>
        <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
    </p>

    <div class="legal-text"><?= nl2br(e($privacyText)) ?></div>
</section>

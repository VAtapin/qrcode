<?php
$contactEmail = (string) setting('legal.contact_email', 'atapin@gmail.com');
$address = trim((string) setting('legal.impressum_address', ''));
$impressumText = trim((string) setting('legal.impressum_text.' . app_locale(), legal_default_impressum_text()));
if ($impressumText === '') {
    $impressumText = legal_default_impressum_text();
}
?>
<section class="panel narrow">
    <h1>Impressum</h1>
    <p class="muted"><?= e(__('legal.impressum_subtitle')) ?></p>

    <dl class="impressum-list">
        <div>
            <dt><?= e(__('legal.private_provider')) ?></dt>
            <dd>Volodymyr Atapin</dd>
        </div>
        <div>
            <dt><?= e(__('legal.email')) ?></dt>
            <dd><a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></dd>
        </div>
        <?php if ($address !== ''): ?>
            <div>
                <dt><?= e(__('legal.address')) ?></dt>
                <dd><?= nl2br(e($address)) ?></dd>
            </div>
        <?php endif; ?>
        <div>
            <dt><?= e(__('legal.technical_implementation')) ?></dt>
            <dd><a href="https://bible-media.de/" target="_blank" rel="noreferrer">Bible Media Agentur</a></dd>
        </div>
    </dl>

    <h2><?= e(__('legal.content_responsible')) ?></h2>
    <p>Volodymyr Atapin</p>

    <h2><?= e(__('legal.note')) ?></h2>
    <p class="legal-text"><?= nl2br(e($impressumText)) ?></p>
</section>

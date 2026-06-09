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
            <dd><a href="mailto:<?= e(setting('legal.contact_email', 'atapin@gmail.com')) ?>"><?= e(setting('legal.contact_email', 'atapin@gmail.com')) ?></a></dd>
        </div>
        <div>
            <dt><?= e(__('legal.technical_implementation')) ?></dt>
            <dd><a href="https://bible-media.de/" target="_blank" rel="noreferrer">Bible Media Agentur</a></dd>
        </div>
    </dl>

    <h2><?= e(__('legal.content_responsible')) ?></h2>
    <p>Volodymyr Atapin</p>

    <h2><?= e(__('legal.note')) ?></h2>
    <p class="muted"><?= e(__('legal.private_note')) ?></p>
</section>

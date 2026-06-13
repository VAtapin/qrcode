<section class="panel">
    <h1><?= e(__('create.title')) ?></h1>
    <p class="muted"><?= e(__('create.subtitle')) ?></p>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/links" class="form">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="locale" value="<?= e(app_locale()) ?>">
        <input type="hidden" name="form_started_at" value="<?= time() ?>">
        <label class="hp-field" aria-hidden="true" tabindex="-1">
            Website
            <input name="website" autocomplete="off" tabindex="-1">
        </label>
        <label>
            <?= e(__('form.title')) ?>
            <input name="title" maxlength="190" required value="<?= e($old['title'] ?? '') ?>">
        </label>
        <label>
            <?= e(__('form.target_url')) ?>
            <input name="target_url" type="url" required placeholder="https://example.com" value="<?= e($old['target_url'] ?? '') ?>">
        </label>
        <label>
            <?= e(__('form.short_code')) ?>
            <input name="short_code" minlength="3" maxlength="50" pattern="[A-Za-z0-9*_]{3,50}" value="<?= e($old['short_code'] ?? '') ?>">
        </label>
        <label>
            <?= e(empty($_SESSION['admin_id']) ? __('form.email') : __('form.author_email')) ?>
            <input name="submitter_email" type="email" <?= empty($_SESSION['admin_id']) ? 'required' : '' ?> value="<?= e($old['submitter_email'] ?? '') ?>">
        </label>
        <label>
            <?= e(__('form.qr_color')) ?>
            <input name="qr_color" type="color" value="<?= e($old['qr_color'] ?? '#000000') ?>">
        </label>
        <label>
            <?= e(__('form.comment')) ?>
            <textarea name="comment" rows="4"><?= e($old['comment'] ?? '') ?></textarea>
        </label>
        <label class="check">
            <input type="checkbox" name="is_public" value="1" <?= !empty($old['is_public']) ? 'checked' : '' ?>>
            <span><?= e(__('form.is_public')) ?></span>
        </label>
        <?php if (!empty($_SESSION['admin_id'])): ?>
            <label class="check">
                <input type="checkbox" name="approve_now" value="1">
                <span><?= e(__('form.approve_now')) ?></span>
            </label>
        <?php endif; ?>
        <label class="check">
            <input type="checkbox" name="agree" value="1" required>
            <span><?= e(__('form.agree')) ?> <a href="<?= e(localized_path('agb')) ?>" target="_blank" rel="noreferrer"><?= e(__('agb.title')) ?></a></span>
        </label>
        <button class="primary" type="submit"><?= e(__('button.create_qr')) ?></button>
    </form>
</section>

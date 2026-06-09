<section class="panel">
    <h1><?= e(__('admin.settings')) ?></h1>
    <p class="muted"><?= e(__('admin.settings_hint')) ?></p>

    <?php if ($message = flash('error')): ?><div class="alert error"><?= e($message) ?></div><?php endif; ?>
    <?php if ($message = flash('success')): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>

    <form method="post" action="/admin/settings" class="form">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="legal_locale" value="<?= e($legalLocale ?? app_locale()) ?>">
        <label>
            <?= e(__('admin.language')) ?>
            <select name="locale" required>
                <?php foreach (supported_locales() as $locale): ?>
                    <option value="<?= e($locale) ?>" <?= ($admin['locale'] ?? default_locale()) === $locale ? 'selected' : '' ?>>
                        <?= e(strtoupper($locale)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <?= e(__('settings.admin_email')) ?>
            <input name="mail_admin_to" type="email" value="<?= e($settings['mail.admin_to'] ?? '') ?>">
        </label>
        <label>
            <?= e(__('settings.mail_from_name')) ?>
            <input name="mail_from_name" required maxlength="120" value="<?= e($settings['mail.from_name'] ?? 'Q to me') ?>">
        </label>
        <label>
            <?= e(__('settings.legal_provider')) ?>
            <textarea name="legal_provider" rows="2"><?= e($settings['legal.provider'] ?? '') ?></textarea>
        </label>
        <label>
            <?= e(__('settings.represented_by')) ?>
            <input name="legal_represented_by" value="<?= e($settings['legal.represented_by'] ?? '') ?>">
        </label>
        <label>
            <?= e(__('settings.content_responsible')) ?>
            <textarea name="legal_content_responsible" rows="2"><?= e($settings['legal.content_responsible'] ?? '') ?></textarea>
        </label>
        <label>
            <?= e(__('settings.contact_email')) ?>
            <input name="legal_contact_email" type="email" required value="<?= e($settings['legal.contact_email'] ?? legal_default_contact_email()) ?>">
        </label>
        <label>
            <?= e(__('settings.phone')) ?>
            <input name="legal_phone" value="<?= e($settings['legal.phone'] ?? legal_default_phone()) ?>">
        </label>
        <div class="settings-section">
            <h2><?= e(__('settings.legal_pages')) ?></h2>
            <p class="muted"><?= e(__('settings.legal_language_hint', ['locale' => strtoupper($legalLocale ?? app_locale())])) ?></p>
            <label>
                <?= e(__('settings.impressum_address')) ?>
                <textarea name="legal_impressum_address" rows="3"><?= e($settings['legal.impressum_address'] ?? '') ?></textarea>
            </label>
            <label>
                <?= e(__('settings.impressum_text')) ?> (<?= e(strtoupper($legalLocale ?? app_locale())) ?>)
                <textarea name="legal_impressum_text" rows="7"><?= e($settings['legal.impressum_text'] ?? '') ?></textarea>
            </label>
            <label>
                <?= e(__('settings.privacy_text')) ?> (<?= e(strtoupper($legalLocale ?? app_locale())) ?>)
                <textarea name="legal_privacy_text" rows="9"><?= e($settings['legal.privacy_text'] ?? '') ?></textarea>
            </label>
        </div>
        <label class="check">
            <input type="checkbox" name="gallery_enabled" value="1" <?= ($settings['gallery.enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
            <span><?= e(__('settings.gallery_enabled')) ?></span>
        </label>
        <div class="actions">
            <button class="primary" type="submit" name="settings_action" value="save"><?= e(__('button.save')) ?></button>
            <button class="button" type="submit" name="settings_action" value="load_legal_defaults">
                <?= e(__('settings.load_legal_defaults')) ?>
            </button>
        </div>
    </form>
</section>

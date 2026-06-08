<section class="gallery-hero">
    <div>
        <p class="eyebrow"><?= e(__('gallery.eyebrow')) ?></p>
        <h1><?= e(__('gallery.title')) ?></h1>
        <p class="muted">
            <?= e($isAdmin ? __('gallery.subtitle_admin') : __('gallery.subtitle_public')) ?>
        </p>
    </div>
    <a class="button primary" href="<?= e(localized_path('new')) ?>"><?= e(__('gallery.create')) ?></a>
</section>

<section class="panel wide gallery-panel">
    <form class="filters" method="get" action="<?= e(localized_path()) ?>">
        <input name="search" placeholder="<?= e(__('gallery.search_placeholder')) ?>" value="<?= e($search) ?>">
        <button type="submit"><?= e(__('gallery.search_button')) ?></button>
        <?php
            $filters = $isAdmin
                ? ['all' => __('filter.all'), 'public' => __('filter.public'), 'private' => __('filter.private'), 'latest' => __('filter.latest')]
                : ['all' => __('filter.all'), 'latest' => __('filter.latest')];
        ?>
        <?php foreach ($filters as $key => $label): ?>
            <a class="filter <?= $filter === $key ? 'active' : '' ?>" href="<?= e(localized_path()) ?>?filter=<?= e($key) ?>&search=<?= e(urlencode($search)) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </form>

    <?php if ($items === []): ?>
        <div class="empty large"><?= e(__('gallery.empty')) ?></div>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($items as $link): ?>
                <article class="qr-card">
                    <div class="qr-card-head">
                        <h2><?= e($link['title']) ?></h2>
                        <?php if ($isAdmin): ?>
                            <span class="badge <?= (int) $link['is_public'] === 1 ? 'green' : '' ?>">
                                <?= e((int) $link['is_public'] === 1 ? __('gallery.badge_public') : __('gallery.badge_private')) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($link['qr_path'])): ?>
                        <div class="qr-frame">
                            <img src="/storage/<?= e($link['qr_path']) ?>" alt="QR <?= e($link['title']) ?>">
                        </div>
                    <?php endif; ?>
                    <code><?= e(url($link['short_code'])) ?></code>
                    <div class="actions">
                        <a class="button" href="/<?= e($link['short_code']) ?>" target="_blank" rel="noreferrer"><?= e(__('button.open')) ?></a>
                        <a class="button" href="<?= e(localized_path('qr/' . $link['short_code'] . '/download')) ?>"><?= e(__('button.download_qr')) ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
        <nav class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= e(localized_path()) ?>?search=<?= e(urlencode($search)) ?>&filter=<?= e($filter) ?>&page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>

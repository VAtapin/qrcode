<section class="panel">
    <h1><?= e(__('admin.dashboard')) ?></h1>
    <div class="stats">
        <a href="/admin/pending"><strong><?= e((string) $stats['pending']) ?></strong><span><?= e(__('admin.pending')) ?></span></a>
        <a href="/admin/approved"><strong><?= e((string) $stats['approved']) ?></strong><span><?= e(__('admin.approved')) ?></span></a>
        <a href="/admin/rejected"><strong><?= e((string) $stats['rejected']) ?></strong><span><?= e(__('admin.rejected')) ?></span></a>
        <a href="/admin/blocked"><strong><?= e((string) $stats['blocked']) ?></strong><span><?= e(__('admin.blocked')) ?></span></a>
        <a href="/admin/blacklist"><strong>BL</strong><span><?= e(__('admin.blacklist')) ?></span></a>
        <div><strong><?= e((string) $stats['clicks']) ?></strong><span><?= e(__('admin.total_clicks')) ?></span></div>
    </div>
</section>

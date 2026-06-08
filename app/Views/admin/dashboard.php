<section class="panel">
    <h1>Панель администратора</h1>
    <div class="stats">
        <a href="/admin/pending"><strong><?= e((string) $stats['pending']) ?></strong><span>На проверке</span></a>
        <a href="/admin/approved"><strong><?= e((string) $stats['approved']) ?></strong><span>Одобрено</span></a>
        <a href="/admin/rejected"><strong><?= e((string) $stats['rejected']) ?></strong><span>Отклонено</span></a>
        <a href="/admin/blocked"><strong><?= e((string) $stats['blocked']) ?></strong><span>Заблокировано</span></a>
        <a href="/admin/blacklist"><strong>BL</strong><span>Чёрный список</span></a>
        <div><strong><?= e((string) $stats['clicks']) ?></strong><span>Переходов всего</span></div>
    </div>
</section>

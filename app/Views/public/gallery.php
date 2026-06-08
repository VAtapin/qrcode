<section class="panel wide">
    <div class="heading-row">
        <div>
            <h1>Галерея QR-кодов</h1>
            <p class="muted">
                <?= $isAdmin ? 'Все одобренные QR-коды, включая приватные.' : 'Публичные QR-коды, одобренные администратором.' ?>
            </p>
        </div>
        <a class="button primary" href="/new">Создать ссылку</a>
    </div>

    <form class="filters" method="get" action="/">
        <input name="search" placeholder="Поиск по названию или коду" value="<?= e($search) ?>">
        <button type="submit">Найти</button>
        <?php
            $filters = $isAdmin
                ? ['all' => 'Все', 'public' => 'Публичные', 'private' => 'Приватные', 'latest' => 'Последние добавленные']
                : ['all' => 'Все', 'latest' => 'Последние добавленные'];
        ?>
        <?php foreach ($filters as $key => $label): ?>
            <a class="filter <?= $filter === $key ? 'active' : '' ?>" href="/?filter=<?= e($key) ?>&search=<?= e(urlencode($search)) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </form>

    <?php if ($items === []): ?>
        <div class="empty large">Ничего не найдено</div>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($items as $link): ?>
                <article class="qr-card">
                    <h2><?= e($link['title']) ?></h2>
                    <?php if (!empty($link['qr_path'])): ?>
                        <img src="/storage/<?= e($link['qr_path']) ?>" alt="QR <?= e($link['title']) ?>">
                    <?php endif; ?>
                    <code><?= e(url($link['short_code'])) ?></code>
                    <?php if ($isAdmin && (int) $link['is_public'] === 0): ?>
                        <span class="badge">приватная</span>
                    <?php endif; ?>
                    <div class="actions">
                        <a class="button" href="/<?= e($link['short_code']) ?>" target="_blank" rel="noreferrer">Открыть</a>
                        <a class="button" href="/qr/<?= e($link['short_code']) ?>/download">Скачать QR</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
        <nav class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a class="<?= $i === $page ? 'active' : '' ?>" href="/?search=<?= e(urlencode($search)) ?>&filter=<?= e($filter) ?>&page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>

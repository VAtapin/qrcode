<section class="gallery-hero">
    <div>
        <p class="eyebrow">q-2.me</p>
        <h1>Короткие ссылки и QR-коды, которые легко передать</h1>
        <p class="muted">
            <?= $isAdmin ? 'Вы видите все одобренные QR-коды, включая приватные.' : 'Публичная галерея одобренных QR-кодов.' ?>
        </p>
    </div>
    <a class="button primary" href="/new">Создать ссылку</a>
</section>

<section class="panel wide gallery-panel">
    <form class="filters" method="get" action="/">
        <input name="search" placeholder="Поиск по названию или коду" value="<?= e($search) ?>">
        <button type="submit">Найти</button>
        <?php
            $filters = $isAdmin
                ? ['all' => 'Все', 'public' => 'Публичные', 'private' => 'Приватные', 'latest' => 'Последние']
                : ['all' => 'Все', 'latest' => 'Последние'];
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
                    <div class="qr-card-head">
                        <h2><?= e($link['title']) ?></h2>
                        <?php if ($isAdmin): ?>
                            <span class="badge <?= (int) $link['is_public'] === 1 ? 'green' : '' ?>">
                                <?= (int) $link['is_public'] === 1 ? 'публичная' : 'приватная' ?>
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

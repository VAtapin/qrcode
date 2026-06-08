<section class="panel narrow">
    <h1><?= e($title ?? __('error.generic_title')) ?></h1>
    <p class="muted"><?= e($message ?? __('error.generic_message')) ?></p>
    <a class="button" href="<?= e(localized_path()) ?>"><?= e(__('button.home')) ?></a>
</section>

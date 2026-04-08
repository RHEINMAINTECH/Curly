<?php $__view->layout('layouts.main'); ?>

<?php $__view->start('head'); ?>
<?php if (!empty($seo['title'])): ?>
<title><?= htmlspecialchars($seo['title']) ?> - <?= htmlspecialchars($settings['site_title'] ?? 'Curly CMS') ?></title>
<?php endif; ?>

<?php if (!empty($seo['description'])): ?>
<meta name="description" content="<?= htmlspecialchars($seo['description']) ?>">
<?php endif; ?>

<?php if (!empty($seo['keywords'])): ?>
<meta name="keywords" content="<?= htmlspecialchars($seo['keywords']) ?>">
<?php endif; ?>
<?php $__view->stop(); ?>

<?php $__view->start('content'); ?>
<article class="page">
    <?= $content ?>
</article>
<?php $__view->stop(); ?>

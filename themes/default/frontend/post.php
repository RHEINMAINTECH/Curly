<?php $__view->layout('layouts.main'); ?>

<?php $__view->start('head'); ?>
<title><?= htmlspecialchars($seo['title'] ?? $post['title']) ?> - <?= htmlspecialchars($settings['site_title'] ?? 'Curly CMS') ?></title>

<?php if (!empty($seo['description'])): ?>
<meta name="description" content="<?= htmlspecialchars($seo['description']) ?>">
<?php endif; ?>

<meta property="og:type" content="article">
<meta property="article:published_time" content="<?= htmlspecialchars($post['published_at']) ?>">
<?php if (!empty($post['author_name'])): ?>
<meta property="article:author" content="<?= htmlspecialchars($post['author_name']) ?>">
<?php endif; ?>
<?php $__view->stop(); ?>

<?php $__view->start('content'); ?>
<div class="container">
    <article class="post">
        <header class="post-header mb-4">
            <h1 class="display-4"><?= htmlspecialchars($post['title']) ?></h1>
            
            <div class="post-meta text-muted mb-3">
                <span class="me-3">
                    <i class="bi bi-calendar"></i>
                    <?= date('F j, Y', strtotime($post['published_at'])) ?>
                </span>
                <?php if (!empty($post['author_name'])): ?>
                <span class="me-3">
                    <i class="bi bi-person"></i>
                    <?= htmlspecialchars($post['author_name']) ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($post['category_name'])): ?>
                <span>
                    <i class="bi bi-folder"></i>
                    <a href="/category/<?= htmlspecialchars($post['category_slug']) ?>" class="text-decoration-none">
                        <?= htmlspecialchars($post['category_name']) ?>
                    </a>
                </span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($post['featured_image'])): ?>
            <img src="<?= htmlspecialchars($post['featured_image']) ?>" 
                 class="img-fluid rounded" 
                 alt="<?= htmlspecialchars($post['title']) ?>">
            <?php endif; ?>
        </header>
        
        <div class="post-content">
            <?= $content ?>
        </div>
        
        <?php if (!empty($post['meta_keywords'])): ?>
        <footer class="post-footer mt-4 pt-4 border-top">
            <div class="tags">
                <?php foreach (explode(',', $post['meta_keywords']) as $tag): ?>
                <span class="badge bg-secondary me-1"><?= htmlspecialchars(trim($tag)) ?></span>
                <?php endforeach; ?>
            </div>
        </footer>
        <?php endif; ?>
    </article>
    
    <?php if (!empty($relatedPosts)): ?>
    <section class="related-posts mt-5">
        <h3>Related Posts</h3>
        <div class="row mt-3">
            <?php foreach ($relatedPosts as $related): ?>
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="/post/<?= htmlspecialchars($related['slug']) ?>" class="text-decoration-none">
                                <?= htmlspecialchars($related['title']) ?>
                            </a>
                        </h5>
                        <small class="text-muted">
                            <?= date('M j, Y', strtotime($related['published_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>
<?php $__view->stop(); ?>

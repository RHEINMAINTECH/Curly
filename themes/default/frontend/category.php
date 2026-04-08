<?php $__view->layout('layouts.main'); ?>

<?php $__view->start('content'); ?>
<div class="container">
    <h1 class="mb-4">Category: <?= htmlspecialchars($category['name']) ?></h1>
    
    <?php if (!empty($category['description'])): ?>
    <p class="lead"><?= htmlspecialchars($category['description']) ?></p>
    <?php endif; ?>
    
    <hr class="my-4">
    
    <?php if (!empty($posts)): ?>
    <div class="row">
        <?php foreach ($posts as $post): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <?php if (!empty($post['featured_image'])): ?>
                <img src="<?= htmlspecialchars($post['featured_image']) ?>" class="card-img-top" alt="">
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="card-title">
                        <a href="/post/<?= htmlspecialchars($post['slug']) ?>">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                    </h5>
                    <p class="card-text">
                        <?= htmlspecialchars($post['excerpt'] ?? \CurlyCMS\Core\Helper::excerpt($post['content'] ?? '', 100)) ?>
                    </p>
                </div>
                <div class="card-footer text-muted">
                    <small><?= date('F j, Y', strtotime($post['published_at'])) ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($pagination['last_page'] > 1): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <?php if ($pagination['current'] > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $pagination['current'] - 1 ?>">Previous</a>
            </li>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
            <li class="page-item <?= $i === $pagination['current'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($pagination['current'] < $pagination['last_page']): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $pagination['current'] + 1 ?>">Next</a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="alert alert-info">
        No posts found in this category.
    </div>
    <?php endif; ?>
</div>
<?php $__view->stop(); ?>

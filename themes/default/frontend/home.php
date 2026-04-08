<?php $__view->layout('layouts.main'); ?>

<?php $__view->start('content'); ?>
<div class="container">
    <?php if (!empty($posts)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <h1>Latest Posts</h1>
            <hr>
        </div>
    </div>
    
    <div class="row">
        <?php foreach ($posts as $post): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <?php if (!empty($post['featured_image'])): ?>
                <img src="<?= htmlspecialchars($post['featured_image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($post['title']) ?>">
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="card-title">
                        <a href="/post/<?= htmlspecialchars($post['slug']) ?>" class="text-decoration-none">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                    </h5>
                    <p class="card-text">
                        <?= htmlspecialchars($post['excerpt'] ?? \CurlyCMS\Core\Helper::excerpt($post['content'] ?? '', 150)) ?>
                    </p>
                    <a href="/post/<?= htmlspecialchars($post['slug']) ?>" class="btn btn-primary btn-sm">Read More</a>
                </div>
                <div class="card-footer text-muted">
                    <small><?= date('F j, Y', strtotime($post['published_at'])) ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-4">
        <a href="/posts" class="btn btn-outline-primary">View All Posts</a>
    </div>
    <?php else: ?>
    <div class="text-center py-5">
        <h1>Welcome to Curly CMS</h1>
        <p class="lead">A modern, AI-native Content Management System</p>
        <hr class="my-4">
        <p>Start creating your content in the admin panel.</p>
        <a href="/admin" class="btn btn-primary btn-lg">Go to Admin</a>
    </div>
    <?php endif; ?>
</div>
<?php $__view->stop(); ?>

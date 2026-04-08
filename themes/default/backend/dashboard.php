<?php $__view->layout('layouts.backend'); ?>

<?php $__view->start('content'); ?>
<div class="container-fluid">
    <h1 class="h3 mb-4">Dashboard</h1>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Pages</h5>
                    <h2 class="display-5"><?= number_format($stats['pages']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Published Posts</h5>
                    <h2 class="display-5"><?= number_format($stats['posts']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Drafts</h5>
                    <h2 class="display-5"><?= number_format($stats['drafts']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Media Files</h5>
                    <h2 class="display-5"><?= number_format($stats['media']) ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Posts -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Posts</h5>
                    <a href="/admin/posts" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentPosts)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentPosts as $post): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="/admin/posts/<?= $post['id'] ?>/edit">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                                <br>
                                <small class="text-muted">
                                    <?= date('M j, Y', strtotime($post['created_at'])) ?>
                                </small>
                            </div>
                            <span class="badge bg-<?= $post['status'] === 'published' ? 'success' : 'warning' ?>">
                                <?= htmlspecialchars($post['status']) ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-muted mb-0">No posts yet. <a href="/admin/posts/create">Create your first post</a>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Pages -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Pages</h5>
                    <a href="/admin/pages" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentPages)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentPages as $page): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="/admin/pages/<?= $page['id'] ?>/edit">
                                    <?= htmlspecialchars($page['title']) ?>
                                </a>
                                <br>
                                <small class="text-muted">
                                    Updated: <?= date('M j, Y', strtotime($page['updated_at'])) ?>
                                </small>
                            </div>
                            <span class="badge bg-<?= $page['status'] === 'published' ? 'success' : 'warning' ?>">
                                <?= htmlspecialchars($page['status']) ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-muted mb-0">No pages yet. <a href="/admin/pages/create">Create your first page</a>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($aiStats)): ?>
    <!-- AI Stats -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">AI Usage (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h3><?= number_format($aiStats['total_requests']) ?></h3>
                            <p class="text-muted">Total Requests</p>
                        </div>
                        <div class="col-md-4">
                            <h3><?= number_format($aiStats['total_tokens']) ?></h3>
                            <p class="text-muted">Tokens Used</p>
                        </div>
                        <div class="col-md-4">
                            <h3><?= round($aiStats['avg_duration']) ?>ms</h3>
                            <p class="text-muted">Avg Response Time</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="/admin/pages/create" class="btn btn-primary me-2 mb-2">
                        <i class="bi bi-plus-circle"></i> New Page
                    </a>
                    <a href="/admin/posts/create" class="btn btn-success me-2 mb-2">
                        <i class="bi bi-plus-circle"></i> New Post
                    </a>
                    <a href="/admin/media" class="btn btn-info me-2 mb-2">
                        <i class="bi bi-upload"></i> Upload Media
                    </a>
                    <a href="/admin/ai" class="btn btn-warning me-2 mb-2">
                        <i class="bi bi-robot"></i> AI Assistant
                    </a>
                    <a href="/admin/settings" class="btn btn-secondary mb-2">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__view->stop(); ?>

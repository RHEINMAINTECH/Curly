<?php $__view->layout('layouts.backend'); ?>

<?php $__view->start('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Posts</h1>
        <a href="/admin/posts/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Post
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search posts..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="/admin/posts" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Posts List -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Author</th>
                        <th>Published</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <a href="/admin/posts/<?= $post['id'] ?>/edit">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($post['category_name']): ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars($post['category_name']) ?></span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $post['status'] === 'published' ? 'success' : 'warning' ?>">
                                <?= htmlspecialchars($post['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($post['author_name'] ?? '-') ?></td>
                        <td>
                            <small>
                                <?= $post['published_at'] ? date('M j, Y', strtotime($post['published_at'])) : '-' ?>
                            </small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <?php if ($post['status'] === 'published'): ?>
                                <a href="/post/<?= $post['slug'] ?>" 
                                   class="btn btn-outline-secondary" 
                                   target="_blank"
                                   title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php endif; ?>
                                <a href="/admin/posts/<?= $post['id'] ?>/edit" 
                                   class="btn btn-outline-primary"
                                   title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-outline-danger"
                                        onclick="deletePost(<?= $post['id'] ?>)"
                                        title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No posts found. <a href="/admin/posts/create">Create your first post</a>.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" action="">
    <?= $__view->csrf() ?>
    <input type="hidden" name="_method" value="DELETE">
</form>

<script>
function deletePost(id) {
    if (confirm('Are you sure you want to delete this post?')) {
        document.getElementById('deleteForm').action = '/admin/posts/' + id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
<?php $__view->stop(); ?>

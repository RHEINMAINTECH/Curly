<?php $__view->layout('layouts.backend'); ?>

<?php $__view->start('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Pages</h1>
        <a href="/admin/pages/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Page
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search pages..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Pages List -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Author</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pages)): ?>
                    <?php foreach ($pages as $page): ?>
                    <tr>
                        <td>
                            <a href="/admin/pages/<?= $page['id'] ?>/edit">
                                <?= htmlspecialchars($page['title']) ?>
                            </a>
                        </td>
                        <td>
                            <code>/<?= htmlspecialchars($page['slug']) ?></code>
                        </td>
                        <td>
                            <span class="badge bg-<?= $page['status'] === 'published' ? 'success' : 'warning' ?>">
                                <?= htmlspecialchars($page['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($page['author_name'] ?? '-') ?></td>
                        <td>
                            <small><?= date('M j, Y', strtotime($page['updated_at'])) ?></small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <?php if ($page['status'] === 'published'): ?>
                                <a href="/page/<?= $page['slug'] ?>" 
                                   class="btn btn-outline-secondary" 
                                   target="_blank"
                                   title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php endif; ?>
                                <a href="/admin/pages/<?= $page['id'] ?>/edit" 
                                   class="btn btn-outline-primary"
                                   title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-outline-danger"
                                        onclick="deletePage(<?= $page['id'] ?>)"
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
                            No pages found. <a href="/admin/pages/create">Create your first page</a>.
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
function deletePage(id) {
    if (confirm('Are you sure you want to delete this page?')) {
        document.getElementById('deleteForm').action = '/admin/pages/' + id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
<?php $__view->stop(); ?>

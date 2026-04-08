<?php $__view->layout('layouts.backend'); ?>

<?php $__view->start('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Users</h1>
        <a href="/admin/users/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New User
        </a>
    </div>
    
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-secondary rounded-circle text-white d-flex align-items-center justify-content-center me-2"
                                     style="width: 32px; height: 32px;">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                                <?= htmlspecialchars($user['name']) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'editor' ? 'primary' : 'secondary') ?>">
                                <?= htmlspecialchars($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>">
                                <?= htmlspecialchars($user['status']) ?>
                            </span>
                        </td>
                        <td>
                            <small><?= date('M j, Y', strtotime($user['created_at'])) ?></small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/admin/users/<?= $user['id'] ?>/edit" 
                                   class="btn btn-outline-primary"
                                   title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($user['id'] != $__view->config('session.user_id')): ?>
                                <button type="button" 
                                        class="btn btn-outline-danger"
                                        onclick="deleteUser(<?= $user['id'] ?>)"
                                        title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No users found.
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
function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user?')) {
        document.getElementById('deleteForm').action = '/admin/users/' + id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
<?php $__view->stop(); ?>

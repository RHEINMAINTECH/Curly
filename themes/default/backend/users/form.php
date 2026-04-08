<?php $__view->layout('layouts.backend'); ?>

<?php $__view->start('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= isset($user) ? 'Edit User' : 'Create User' ?></h1>
        <a href="/admin/users" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Users
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?= isset($user) ? '/admin/users/' . $user['id'] : '/admin/users' ?>">
                        <?= $__view->csrf() ?>
                        
                        <?php if (isset($user)): ?>
                        <input type="hidden" name="_method" value="PUT">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                                   required>
                            <?= $__view->error('name') ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                   required>
                            <?= $__view->error('email') ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Password <?= isset($user) ? '(leave blank to keep current)' : '*' ?>
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password"
                                   <?= isset($user) ? '' : 'required' ?>>
                            <?= $__view->error('password') ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role">
                                        <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                                            Admin
                                        </option>
                                        <option value="editor" <?= ($user['role'] ?? '') === 'editor' ? 'selected' : '' ?>>
                                            Editor
                                        </option>
                                        <option value="author" <?= ($user['role'] ?? '') === 'author' ? 'selected' : '' ?>>
                                            Author
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?= ($user['status'] ?? '') === 'active' ? 'selected' : '' ?>>
                                            Active
                                        </option>
                                        <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                            Inactive
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save User
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <?php if (isset($user)): ?>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Created:</strong> 
                        <?= date('F j, Y, g:i a', strtotime($user['created_at'])) ?>
                    </p>
                    <p class="mb-0">
                        <strong>Last Login:</strong>
                        <?= isset($user['last_login']) ? date('F j, Y, g:i a', strtotime($user['last_login'])) : 'Never' ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__view->stop(); ?>

<?php $__view->layout('layouts.backend'); ?>

<?php $__view->start('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Extensions</h1>
    </div>
    
    <?php if (empty($extensions)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-puzzle fs-1 text-muted"></i>
            <h5 class="mt-3">No Extensions Found</h5>
            <p class="text-muted">
                Place extension folders in the <code>/extensions</code> directory.
            </p>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($extensions as $name => $ext): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title mb-0"><?= htmlspecialchars($ext['title']) ?></h5>
                        <?php if ($ext['active']): ?>
                        <span class="badge bg-success">Active</span>
                        <?php elseif ($ext['installed']): ?>
                        <span class="badge bg-warning">Inactive</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Not Installed</span>
                        <?php endif; ?>
                    </div>
                    <h6 class="card-subtitle text-muted mb-2"><?= htmlspecialchars($name) ?></h6>
                    <p class="card-text small">
                        <?= htmlspecialchars($ext['description'] ?: 'No description available.') ?>
                    </p>
                    <p class="card-text">
                        <small class="text-muted">
                            Version: <?= htmlspecialchars($ext['version']) ?>
                            <?php if ($ext['author']): ?>
                            <br>Author: <?= htmlspecialchars($ext['author']) ?>
                            <?php endif; ?>
                        </small>
                    </p>
                </div>
                <div class="card-footer">
                    <?php if (!$ext['installed']): ?>
                    <form method="POST" action="/admin/extensions/<?= htmlspecialchars($name) ?>/install" class="d-inline">
                        <?= $__view->csrf() ?>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-download"></i> Install
                        </button>
                    </form>
                    <?php elseif (!$ext['active']): ?>
                    <form method="POST" action="/admin/extensions/<?= htmlspecialchars($name) ?>/activate" class="d-inline">
                        <?= $__view->csrf() ?>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-play-circle"></i> Activate
                        </button>
                    </form>
                    <form method="POST" action="/admin/extensions/<?= htmlspecialchars($name) ?>/uninstall" class="d-inline">
                        <?= $__view->csrf() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                onclick="return confirm('Uninstall this extension?')">
                            <i class="bi bi-trash"></i> Uninstall
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="/admin/extensions/<?= htmlspecialchars($name) ?>/deactivate" class="d-inline">
                        <?= $__view->csrf() ?>
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="bi bi-pause-circle"></i> Deactivate
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php $__view->stop(); ?>

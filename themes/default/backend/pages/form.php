<?php $__view->layout('layouts.backend'); ?>

<?php $__view->start('head'); ?>
<style>
.structure-editor {
    border: 2px dashed #dee2e6;
    min-height: 200px;
    padding: 1rem;
    background: #f8f9fa;
}
.component-item {
    cursor: move;
    padding: 0.5rem;
    margin: 0.25rem;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
}
</style>
<?php $__view->stop(); ?>

<?php $__view->start('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= isset($page) ? 'Edit Page' : 'Create Page' ?></h1>
        <a href="/admin/pages" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Pages
        </a>
    </div>
    
    <form method="POST" action="<?= isset($page) ? '/admin/pages/' . $page['id'] : '/admin/pages' ?>">
        <?= $__view->csrf() ?>
        
        <?php if (isset($page)): ?>
        <input type="hidden" name="_method" value="PUT">
        <?php endif; ?>
        
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="title" 
                                   name="title" 
                                   value="<?= htmlspecialchars($page['title'] ?? '') ?>"
                                   required>
                            <?= $__view->error('title') ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug *</label>
                            <div class="input-group">
                                <span class="input-group-text">/page/</span>
                                <input type="text" 
                                       class="form-control" 
                                       id="slug" 
                                       name="slug" 
                                       value="<?= htmlspecialchars($page['slug'] ?? '') ?>"
                                       required>
                            </div>
                            <?= $__view->error('slug') ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control" 
                                      id="content" 
                                      name="content" 
                                      rows="10"><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Page Structure (JSON)</label>
                            <textarea class="form-control font-monospace" 
                                      id="structure" 
                                      name="structure" 
                                      rows="10"><?= htmlspecialchars(json_encode($structure, JSON_PRETTY_PRINT)) ?></textarea>
                            <small class="text-muted">
                                Define the page layout using JSON structure. Supports Bootstrap 5 components.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Publish</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>
                                    Draft
                                </option>
                                <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>
                                    Published
                                </option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Parent Page</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">None (Top Level)</option>
                                <?php foreach ($parentPages as $parent): ?>
                                <option value="<?= $parent['id'] ?>" 
                                        <?= ($page['parent_id'] ?? '') == $parent['id'] ? 'selected' : '' ?>
                                        <?= ($page['id'] ?? 0) == $parent['id'] ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($parent['title']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="sort_order" 
                                   name="sort_order" 
                                   value="<?= htmlspecialchars($page['sort_order'] ?? 0) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="template" class="form-label">Template</label>
                            <select class="form-select" id="template" name="template">
                                <option value="">Default</option>
                                <?php foreach ($templates as $name => $label): ?>
                                <option value="<?= htmlspecialchars($name) ?>" 
                                        <?= ($page['template'] ?? '') === $name ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <hr>
                        
                        <?php if ($ai): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" 
                                   id="ai_optimize_seo" 
                                   name="ai_optimize_seo" 
                                   value="1">
                            <label class="form-check-label" for="ai_optimize_seo">
                                AI Optimize SEO
                            </label>
                            <small class="d-block text-muted">
                                Let AI generate meta tags
                            </small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Page
                            </button>
                            
                            <?php if (isset($page)): ?>
                            <button type="button" class="btn btn-outline-secondary" onclick="duplicatePage()">
                                <i class="bi bi-copy"></i> Duplicate
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- SEO Settings -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">SEO Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="meta_title" class="form-label">Meta Title</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="meta_title" 
                                   name="meta_title" 
                                   value="<?= htmlspecialchars($page['meta_title'] ?? '') ?>"
                                   maxlength="60">
                            <small class="text-muted">Max 60 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="form-control" 
                                      id="meta_description" 
                                      name="meta_description" 
                                      rows="2"
                                      maxlength="160"><?= htmlspecialchars($page['meta_description'] ?? '') ?></textarea>
                            <small class="text-muted">Max 160 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="meta_keywords" 
                                   name="meta_keywords" 
                                   value="<?= htmlspecialchars($page['meta_keywords'] ?? '') ?>">
                            <small class="text-muted">Comma separated</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="og_image" class="form-label">OG Image URL</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="og_image" 
                                   name="og_image" 
                                   value="<?= htmlspecialchars($page['og_image'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Auto-generate slug from title
document.getElementById('title').addEventListener('input', function(e) {
    <?php if (!isset($page)): ?>
    const slug = e.target.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
    document.getElementById('slug').value = slug;
    <?php endif; ?>
});

<?php if (isset($page)): ?>
function duplicatePage() {
    if (confirm('Create a copy of this page?')) {
        fetch('/admin/pages/<?= $page['id'] ?>/duplicate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(response => {
            if (response.ok) {
                window.location.href = '/admin/pages';
            }
        });
    }
}
<?php endif; ?>
</script>
<?php $__view->stop(); ?>

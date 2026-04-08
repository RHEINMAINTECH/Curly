<?php $__view->layout('layouts.backend'); ?>

<?php $__view->start('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= isset($post) ? 'Edit Post' : 'Create Post' ?></h1>
        <a href="/admin/posts" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Posts
        </a>
    </div>
    
    <form method="POST" action="<?= isset($post) ? '/admin/posts/' . $post['id'] : '/admin/posts' ?>">
        <?= $__view->csrf() ?>
        
        <?php if (isset($post)): ?>
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
                                   value="<?= htmlspecialchars($post['title'] ?? '') ?>"
                                   required>
                            <?= $__view->error('title') ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug *</label>
                            <div class="input-group">
                                <span class="input-group-text">/post/</span>
                                <input type="text" 
                                       class="form-control" 
                                       id="slug" 
                                       name="slug" 
                                       value="<?= htmlspecialchars($post['slug'] ?? '') ?>"
                                       required>
                            </div>
                            <?= $__view->error('slug') ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea class="form-control" 
                                      id="excerpt" 
                                      name="excerpt" 
                                      rows="3"><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
                            <small class="text-muted">Brief summary for listings and SEO</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control" 
                                      id="content" 
                                      name="content" 
                                      rows="15"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Post Structure (JSON)</label>
                            <textarea class="form-control font-monospace" 
                                      id="structure" 
                                      name="structure" 
                                      rows="10"><?= htmlspecialchars(json_encode($structure, JSON_PRETTY_PRINT)) ?></textarea>
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
                                <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>
                                    Draft
                                </option>
                                <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>
                                    Published
                                </option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" 
                                        <?= ($post['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="featured_image" class="form-label">Featured Image URL</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="featured_image" 
                                   name="featured_image" 
                                   value="<?= htmlspecialchars($post['featured_image'] ?? '') ?>">
                        </div>
                        
                        <?php if ($ai): ?>
                        <hr>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" 
                                   id="ai_optimize_seo" 
                                   name="ai_optimize_seo" 
                                   value="1">
                            <label class="form-check-label" for="ai_optimize_seo">
                                AI Optimize SEO
                            </label>
                        </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Post
                            </button>
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
                                   value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>"
                                   maxlength="60">
                        </div>
                        
                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="form-control" 
                                      id="meta_description" 
                                      name="meta_description" 
                                      rows="2"
                                      maxlength="160"><?= htmlspecialchars($post['meta_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="meta_keywords" 
                                   name="meta_keywords" 
                                   value="<?= htmlspecialchars($post['meta_keywords'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('title').addEventListener('input', function(e) {
    <?php if (!isset($post)): ?>
    const slug = e.target.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
    document.getElementById('slug').value = slug;
    <?php endif; ?>
});
</script>
<?php $__view->stop(); ?>

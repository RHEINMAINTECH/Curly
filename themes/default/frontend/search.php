<?php $__view->layout('layouts.main'); ?>

<?php $__view->start('content'); ?>
<div class="container">
    <h1 class="mb-4">Search Results</h1>
    
    <form method="GET" action="/search" class="mb-4">
        <div class="input-group">
            <input type="text" 
                   name="q" 
                   class="form-control" 
                   placeholder="Search..." 
                   value="<?= htmlspecialchars($query) ?>">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-search"></i> Search
            </button>
        </div>
    </form>
    
    <?php if (!empty($query)): ?>
    <?php if (!empty($results)): ?>
    <p class="text-muted mb-4">
        Found <?= count($results) ?> result(s) for "<?= htmlspecialchars($query) ?>"
    </p>
    
    <div class="list-group">
        <?php foreach ($results as $result): ?>
        <a href="/<?= $result['type'] ?>/<?= htmlspecialchars($result['slug']) ?>" 
           class="list-group-item list-group-item-action">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1"><?= htmlspecialchars($result['title']) ?></h5>
                <small class="text-uppercase"><?= $result['type'] ?></small>
            </div>
            <p class="mb-1 text-muted">
                <?= htmlspecialchars(\CurlyCMS\Core\Helper::excerpt($result['excerpt'], 150)) ?>
            </p>
        </a>
        <?php endforeach; ?>
    </div>
    
    <?php else: ?>
    <div class="alert alert-info">
        No results found for "<?= htmlspecialchars($query) ?>". Try different keywords.
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php $__view->stop(); ?>

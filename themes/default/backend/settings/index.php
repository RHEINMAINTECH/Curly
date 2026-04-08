<?php $__view->layout('layouts.backend'); ?>

<?php $__view->start('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Settings</h1>
    </div>
    
    <form method="POST" action="/admin/settings">
        <?= $__view->csrf() ?>
        <input type="hidden" name="_method" value="PUT">
        
        <div class="row">
            <div class="col-lg-8">
                <!-- General Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">General Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="site_title" class="form-label">Site Title</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="site_title" 
                                   name="settings[site_title]" 
                                   value="<?= htmlspecialchars($settings['site_title'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_description" class="form-label">Site Description</label>
                            <textarea class="form-control" 
                                      id="site_description" 
                                      name="settings[site_description]" 
                                      rows="2"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_url" class="form-label">Site URL</label>
                            <input type="url" 
                                   class="form-control" 
                                   id="site_url" 
                                   name="settings[site_url]" 
                                   value="<?= htmlspecialchars($settings['site_url'] ?? '') ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="site_language" class="form-label">Language</label>
                                    <select class="form-select" id="site_language" name="settings[site_language]">
                                        <option value="en" <?= ($settings['site_language'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                                        <option value="de" <?= ($settings['site_language'] ?? '') === 'de' ? 'selected' : '' ?>>German</option>
                                        <option value="fr" <?= ($settings['site_language'] ?? '') === 'fr' ? 'selected' : '' ?>>French</option>
                                        <option value="es" <?= ($settings['site_language'] ?? '') === 'es' ? 'selected' : '' ?>>Spanish</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="timezone" class="form-label">Timezone</label>
                                    <select class="form-select" id="timezone" name="settings[timezone]">
                                        <option value="Europe/Berlin" <?= ($settings['timezone'] ?? '') === 'Europe/Berlin' ? 'selected' : '' ?>>Europe/Berlin</option>
                                        <option value="Europe/London" <?= ($settings['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' ?>>Europe/London</option>
                                        <option value="America/New_York" <?= ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>America/New_York</option>
                                        <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Content Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Content Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="posts_per_page" class="form-label">Posts Per Page</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="posts_per_page" 
                                           name="settings[posts_per_page]" 
                                           value="<?= htmlspecialchars($settings['posts_per_page'] ?? 10) ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="theme" class="form-label">Active Theme</label>
                                    <select class="form-select" id="theme" name="settings[theme]">
                                        <?php foreach ($themes as $name => $info): ?>
                                        <option value="<?= htmlspecialchars($name) ?>" 
                                                <?= ($settings['theme'] ?? 'default') === $name ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($info['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Footer Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="footer_text" class="form-label">Footer Text</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="footer_text" 
                                   name="settings[footer_text]" 
                                   value="<?= htmlspecialchars($settings['footer_text'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Save Button -->
                <div class="card mb-4">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Save Settings
                        </button>
                    </div>
                </div>
                
                <!-- Admin Email -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Admin Email</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="admin_email" class="form-label">Admin Email</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="admin_email" 
                                   name="settings[admin_email]" 
                                   value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <!-- System Info -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">System Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <strong>CMS Version:</strong> <?= CMS_VERSION ?>
                            </li>
                            <li class="mb-2">
                                <strong>PHP Version:</strong> <?= PHP_VERSION ?>
                            </li>
                            <li class="mb-2">
                                <strong>Database:</strong> SQLite
                            </li>
                            <li>
                                <strong>Environment:</strong> <?= getenv('APP_ENV') ?: 'production' ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php $__view->stop(); ?>

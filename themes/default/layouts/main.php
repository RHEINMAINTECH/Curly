<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Curly CMS <?= CMS_VERSION ?>">
    
    <title><?= htmlspecialchars($title ?? $settings['site_title'] ?? 'Curly CMS') ?></title>
    
    <?php if (!empty($seo['description'])): ?>
    <meta name="description" content="<?= htmlspecialchars($seo['description']) ?>">
    <?php elseif (!empty($settings['site_description'])): ?>
    <meta name="description" content="<?= htmlspecialchars($settings['site_description']) ?>">
    <?php endif; ?>
    
    <?php if (!empty($seo['keywords'])): ?>
    <meta name="keywords" content="<?= htmlspecialchars($seo['keywords']) ?>">
    <?php endif; ?>
    
    <?php if (!empty($seo['og_image'])): ?>
    <meta property="og:image" content="<?= htmlspecialchars($seo['og_image']) ?>">
    <?php endif; ?>
    
    <meta property="og:title" content="<?= htmlspecialchars($title ?? '') ?>">
    <meta property="og:type" content="<?= isset($post) ? 'article' : 'website' ?>">
    <meta property="og:url" content="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Theme CSS -->
    <link href="<?= $__view->asset('themes/default/css/style.css') ?>" rel="stylesheet">
    
    <?= $__view->section('head') ?>
</head>
<body class="<?= isset($bodyClass) ? htmlspecialchars($bodyClass) : '' ?>">
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= $__view->url() ?>">
                <?= htmlspecialchars($settings['site_title'] ?? 'Curly CMS') ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <?php
                $mainMenu = $__view->config('app.menus.main');
                if ($mainMenu):
                ?>
                <ul class="navbar-nav ms-auto">
                    <?php foreach ($mainMenu as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $__view->isActive($item['url']) ? 'active' : '' ?>" 
                           href="<?= htmlspecialchars($item['url']) ?>">
                            <?= htmlspecialchars($item['title']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $__view->isActive('/') && !strpos($_SERVER['REQUEST_URI'], 'page') ? 'active' : '' ?>" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $__view->isActive('/post') ? 'active' : '' ?>" href="/posts">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $__view->isActive('/page/about') ? 'active' : '' ?>" href="/page/about">About</a>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="py-4">
        <?= $__view->section('content') ?>
        
        <?php if (isset($content)): ?>
        <div class="container">
            <?= $content ?>
        </div>
        <?php endif; ?>
    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?= htmlspecialchars($settings['site_title'] ?? 'Curly CMS') ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($settings['site_description'] ?? '') ?></p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-light">Home</a></li>
                        <li><a href="/posts" class="text-light">Blog</a></li>
                        <li><a href="/page/about" class="text-light">About</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Legal</h6>
                    <ul class="list-unstyled">
                        <li><a href="/page/privacy-policy" class="text-light">Privacy Policy</a></li>
                        <li><a href="/page/terms" class="text-light">Terms</a></li>
                        <li><a href="/page/imprint" class="text-light">Imprint</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-12 text-center text-muted">
                    <p><?= htmlspecialchars($settings['footer_text'] ?? 'Powered by Curly CMS') ?></p>
                    <p class="small">&copy; <?= date('Y') ?> RheinMainTech GmbH. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Theme JS -->
    <script src="<?= $__view->asset('themes/default/js/main.js') ?>"></script>
    
    <?= $__view->section('scripts') ?>
</body>
</html>

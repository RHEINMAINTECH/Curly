<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Dashboard') ?> - Curly CMS</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Backend CSS -->
    <link href="<?= $__view->asset('css/backend.css') ?>" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 250px;
        }
        body {
            min-height: 100vh;
        }
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #212529;
            padding-top: 60px;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.75rem 1rem;
            border-radius: 0;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: var(--bs-primary);
        }
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
        }
        .top-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            z-index: 1000;
            display: flex;
            align-items: center;
            padding: 0 1rem;
        }
        .top-navbar .brand {
            width: var(--sidebar-width);
            font-weight: bold;
            color: var(--bs-primary);
        }
        .content-wrapper {
            margin-top: 60px;
        }
    </style>
    
    <?= $__view->section('head') ?>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="top-navbar">
        <div class="brand">
            <i class="bi bi-bootstrap"></i> Curly CMS
        </div>
        <div class="ms-auto d-flex align-items-center">
            <span class="me-3">
                <i class="bi bi-person"></i>
                <?= htmlspecialchars($__view->config('session.user_name', 'Admin')) ?>
            </span>
            <a href="/admin/logout" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </nav>
    
    <!-- Sidebar -->
    <nav class="sidebar">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= ($_SERVER['REQUEST_URI'] === '/admin' || $_SERVER['REQUEST_URI'] === '/admin/') ? 'active' : '' ?>" href="/admin">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $__view->isActive('/admin/pages') ? 'active' : '' ?>" href="/admin/pages">
                    <i class="bi bi-file-earmark-text"></i> Pages
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $__view->isActive('/admin/posts') ? 'active' : '' ?>" href="/admin/posts">
                    <i class="bi bi-journal-text"></i> Posts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $__view->isActive('/admin/media') ? 'active' : '' ?>" href="/admin/media">
                    <i class="bi bi-images"></i> Media
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $__view->isActive('/admin/menus') ? 'active' : '' ?>" href="/admin/menus">
                    <i class="bi bi-list"></i> Menus
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $__view->isActive('/admin/templates') ? 'active' : '' ?>" href="/admin/templates">
                    <i class="bi bi-layout-text-window-reverse"></i> Templates
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $__view->isActive('/admin/extensions') ? 'active' : '' ?>" href="/admin/extensions">
                    <i class="bi bi-puzzle"></i> Extensions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $__view->isActive('/admin/ai') ? 'active' : '' ?>" href="/admin/ai">
                    <i class="bi bi-robot"></i> AI Assistant
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $__view->isActive('/admin/users') ? 'active' : '' ?>" href="/admin/users">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $__view->isActive('/admin/settings') ? 'active' : '' ?>" href="/admin/settings">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <!-- Flash Messages -->
            <?php if ($__view->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($__view->flash('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($__view->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($__view->flash('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Page Content -->
            <?= $__view->section('content') ?>
            <?= $content ?? '' ?>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <?= $__view->section('scripts') ?>
</body>
</html>

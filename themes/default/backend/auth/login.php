<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Curly CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            max-width: 400px;
            margin: 0 auto;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card card">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h1 class="h3 mb-1">Curly CMS</h1>
                    <p class="text-muted">RheinMainTech GmbH</p>
                </div>
                
                <?php if ($__view->hasFlash('error')): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($__view->flash('error')) ?>
                </div>
                <?php endif; ?>
                
                <?php if ($__view->hasFlash('success')): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($__view->flash('success')) ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="/admin/login">
                    <?= $__view->csrf() ?>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   required
                                   autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <a href="/admin/forgot-password" class="text-muted small">
                            Forgot password?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

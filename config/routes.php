<?php
/**
 * Routes Configuration
 */

use CurlyCMS\Core\Router;

/** @var Router $router */

// Frontend routes
$router->get('/', 'FrontendController@home');
$router->get('/page/{slug}', 'FrontendController@page');
$router->get('/post/{slug}', 'FrontendController@post');
$router->get('/category/{slug}', 'FrontendController@category');
$router->get('/search', 'FrontendController@search');
$router->get('/sitemap.xml', 'FrontendController@sitemap');
$router->get('/robots.txt', 'FrontendController@robots');

// Admin routes group
$router->group(['prefix' => '/admin', 'middleware' => ['auth']], function (Router $router) {
    // Dashboard
    $router->get('', 'BackendController@dashboard');
    $router->get('/', 'BackendController@dashboard');
    
    // Pages
    $router->get('/pages', 'PageController@index');
    $router->get('/pages/create', 'PageController@create');
    $router->post('/pages', 'PageController@store');
    $router->get('/pages/{id}/edit', 'PageController@edit');
    $router->put('/pages/{id}', 'PageController@update');
    $router->delete('/pages/{id}', 'PageController@destroy');
    $router->post('/pages/{id}/duplicate', 'PageController@duplicate');
    
    // Posts
    $router->get('/posts', 'PostController@index');
    $router->get('/posts/create', 'PostController@create');
    $router->post('/posts', 'PostController@store');
    $router->get('/posts/{id}/edit', 'PostController@edit');
    $router->put('/posts/{id}', 'PostController@update');
    $router->delete('/posts/{id}', 'PostController@destroy');
    
    // Media
    $router->get('/media', 'MediaController@index');
    $router->post('/media/upload', 'MediaController@upload');
    $router->delete('/media/{id}', 'MediaController@destroy');
    
    // Menus
    $router->get('/menus', 'MenuController@index');
    $router->post('/menus', 'MenuController@store');
    $router->put('/menus/{id}', 'MenuController@update');
    $router->delete('/menus/{id}', 'MenuController@destroy');
    
    // Extensions
    $router->get('/extensions', 'ExtensionController@index');
    $router->post('/extensions/{name}/install', 'ExtensionController@install');
    $router->post('/extensions/{name}/activate', 'ExtensionController@activate');
    $router->post('/extensions/{name}/deactivate', 'ExtensionController@deactivate');
    $router->delete('/extensions/{name}', 'ExtensionController@uninstall');
    
    // Settings
    $router->get('/settings', 'SettingController@index');
    $router->put('/settings', 'SettingController@update');
    
    // Users
    $router->get('/users', 'UserController@index');
    $router->get('/users/create', 'UserController@create');
    $router->post('/users', 'UserController@store');
    $router->get('/users/{id}/edit', 'UserController@edit');
    $router->put('/users/{id}', 'UserController@update');
    $router->delete('/users/{id}', 'UserController@destroy');
    
    // AI Assistant
    $router->get('/ai', 'AIController@index');
    $router->post('/ai/generate', 'AIController@generate');
    $router->post('/ai/generate-structure', 'AIController@generateStructure');
    $router->post('/ai/optimize-seo', 'AIController@optimizeSEO');
    $router->post('/ai/translate', 'AIController@translate');
    
    // Templates
    $router->get('/templates', 'TemplateController@index');
    $router->get('/templates/{id}', 'TemplateController@show');
    $router->post('/templates', 'TemplateController@store');
    $router->put('/templates/{id}', 'TemplateController@update');
    $router->delete('/templates/{id}', 'TemplateController@destroy');
});

// Authentication routes
$router->get('/admin/login', 'AuthController@loginForm');
$router->post('/admin/login', 'AuthController@login');
$router->get('/admin/logout', 'AuthController@logout');
$router->get('/admin/forgot-password', 'AuthController@forgotPasswordForm');
$router->post('/admin/forgot-password', 'AuthController@forgotPassword');
$router->get('/admin/reset-password/{token}', 'AuthController@resetPasswordForm');
$router->post('/admin/reset-password', 'AuthController@resetPassword');

// API routes
$router->group(['prefix' => '/api'], function (Router $router) {
    // AI API
    $router->post('/ai/chat', 'APIController@aiChat');
    $router->post('/ai/complete', 'APIController@aiComplete');
    $router->post('/ai/embed', 'APIController@aiEmbed');
    
    // Content API
    $router->get('/pages', 'APIController@pages');
    $router->get('/posts', 'APIController@posts');
    $router->get('/media', 'APIController@media');
    
    // Webhooks
    $router->post('/webhooks/ai', 'WebhookController@ai');
    $router->post('/webhooks/extension/{name}', 'WebhookController@extension');
    
    // MCS (Model Context Server) Protocol
    $router->post('/mcs/context', 'MCSController@context');
    $router->post('/mcs/execute', 'MCSController@execute');
    
    // A2A (Agent-to-Agent) Protocol
    $router->post('/a2a/message', 'A2AController@message');
    $router->post('/a2a/task', 'A2AController@task');
    $router->post('/a2a/status', 'A2AController@status');
});

// Add middleware
$router->addMiddleware('auth', function () {
    $session = \CurlyCMS\Core\App::getInstance()->getSession();
    
    if (!$session->has('user_id')) {
        \CurlyCMS\Core\Helper::redirect('/admin/login');
        return false;
    }
    
    return true;
});

$router->addMiddleware('admin', function () {
    $session = \CurlyCMS\Core\App::getInstance()->getSession();
    $db = \CurlyCMS\Core\App::getInstance()->getDatabase();
    
    if (!$session->has('user_id')) {
        \CurlyCMS\Core\Helper::redirect('/admin/login');
        return false;
    }
    
    $user = $db->fetch(
        "SELECT role FROM users WHERE id = ?",
        [$session->get('user_id')]
    );
    
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo 'Access denied';
        return false;
    }
    
    return true;
});

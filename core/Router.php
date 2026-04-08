<?php
/**
 * Router Class
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

use CurlyCMS\Core\HttpException;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $prefix = '';
    private array $currentGroupMiddleware = [];

    public function __construct()
    {
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        $routesFile = CMS_ROOT . '/config/routes.php';
        
        if (file_exists($routesFile)) {
            $router = $this;
            require $routesFile;
        }
    }

    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function patch(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    public function any(string $path, $handler, array $middleware = []): void
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            $this->addRoute($method, $path, $handler, $middleware);
        }
    }

    public function group(array $options, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->currentGroupMiddleware;
        
        if (isset($options['prefix'])) {
            $this->prefix .= $options['prefix'];
        }
        
        if (isset($options['middleware'])) {
            $this->currentGroupMiddleware = array_merge(
                $this->currentGroupMiddleware,
                (array) $options['middleware']
            );
        }
        
        $callback($this);
        
        $this->prefix = $previousPrefix;
        $this->currentGroupMiddleware = $previousMiddleware;
    }

    private function addRoute(string $method, string $path, $handler, array $middleware): void
    {
        $path = $this->prefix . $path;
        $path = rtrim($path, '/') ?: '/';
        
        $allMiddleware = array_merge($this->currentGroupMiddleware, $middleware);
        
        // Convert path to regex pattern
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $allMiddleware
        ];
    }

    public function addMiddleware(string $name, callable $callback): void
    {
        $this->middleware[$name] = $callback;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rawurldecode($uri);
        
        // Handle PUT/DELETE method override
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Run middleware
                foreach ($route['middleware'] as $middlewareName) {
                    if (isset($this->middleware[$middlewareName])) {
                        $result = call_user_func($this->middleware[$middlewareName]);
                        if ($result === false) {
                            return;
                        }
                    }
                }
                
                $this->executeHandler($route['handler'], $params);
                return;
            }
        }
        
        // No route found - check for frontend page
        $this->handleNotFound($uri);
    }

    private function executeHandler($handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }
        
        if (is_string($handler)) {
            $parts = explode('@', $handler);
            $controllerClass = 'CurlyCMS\\App\\Controllers\\' . $parts[0];
            $method = $parts[1] ?? 'index';
            
            if (!class_exists($controllerClass)) {
                throw new HttpException(500, "Controller not found: {$controllerClass}");
            }
            
            $controller = new $controllerClass();
            
            if (!method_exists($controller, $method)) {
                throw new HttpException(500, "Method not found: {$controllerClass}::{$method}");
            }
            
            call_user_func_array([$controller, $method], $params);
            return;
        }
        
        throw new HttpException(500, 'Invalid route handler');
    }

    private function handleNotFound(string $uri): void
    {
        // Try to find a matching page in the CMS
        $pageController = new \CurlyCMS\App\Controllers\FrontendController();
        
        if ($pageController->resolvePage($uri)) {
            return;
        }
        
        http_response_code(404);
        
        $errorFile = CMS_ROOT . '/themes/default/errors/404.php';
        if (file_exists($errorFile)) {
            include $errorFile;
        } else {
            echo '<h1>404 - Page Not Found</h1>';
        }
    }
}

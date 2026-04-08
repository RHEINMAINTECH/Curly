<?php
/**
 * Main Application Class
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

use CurlyCMS\Core\Database;
use CurlyCMS\Core\Router;
use CurlyCMS\Core\Session;
use CurlyCMS\Core\Security;
use CurlyCMS\Core\Cache;
use CurlyCMS\Core\AI\AIService;
use CurlyCMS\Core\HttpException;

class App
{
    private static ?App $instance = null;
    private Router $router;
    private Database $database;
    private Session $session;
    private Security $security;
    private Cache $cache;
    private ?AIService $ai = null;
    private array $config = [];
    private array $services = [];

    public function __construct()
    {
        self::$instance = $this;
        
        // Load configuration
        $this->loadConfig();
        
        // Initialize core services
        $this->initializeServices();
        
        // Set up error handling
        $this->setupErrorHandling();
    }

    public static function getInstance(): ?App
    {
        return self::$instance;
    }

    private function loadConfig(): void
    {
        $configDir = CMS_ROOT . '/config';
        
        $configFiles = [
            'app',
            'database',
            'ai',
            'routes',
            'security',
            'extensions'
        ];
        
        foreach ($configFiles as $name) {
            $file = "{$configDir}/{$name}.php";
            if (file_exists($file)) {
                $this->config[$name] = require $file;
            }
        }
        
        // Load environment variables into config
        $this->config['env'] = [
            'name' => getenv('APP_NAME') ?: 'Curly CMS',
            'debug' => getenv('APP_DEBUG') === 'true',
            'url' => getenv('APP_URL') ?: 'http://localhost',
            'key' => getenv('APP_KEY') ?: '',
            'env' => getenv('APP_ENV') ?: 'production'
        ];
    }

    private function initializeServices(): void
    {
        // Database
        $this->database = new Database($this->config['database'] ?? []);
        
        // Session
        $this->session = new Session($this->config['app']['session'] ?? []);
        
        // Security
        $this->security = new Security($this->config['security'] ?? []);
        
        // Cache
        $this->cache = new Cache($this->config['app']['cache'] ?? []);
        
        // Router
        $this->router = new Router();
        
        // AI Service
        if (!empty($this->config['ai'])) {
            $this->ai = new AIService($this->config['ai']);
        }
    }

    private function setupErrorHandling(): void
    {
        set_exception_handler(function (\Throwable $e) {
            $this->handleException($e);
        });
        
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    public function run(): void
    {
        // Start session
        $this->session->start();
        
        // Security: CSRF validation for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->security->validateCSRF();
        }
        
        // Route the request
        $this->router->dispatch();
    }

    public function getConfig(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }
        
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function getSecurity(): Security
    {
        return $this->security;
    }

    public function getCache(): Cache
    {
        return $this->cache;
    }

    public function getAI(): ?AIService
    {
        return $this->ai;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function setService(string $name, $service): void
    {
        $this->services[$name] = $service;
    }

    public function getService(string $name)
    {
        return $this->services[$name] ?? null;
    }

    private function handleException(\Throwable $e): void
    {
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;
        
        http_response_code($statusCode);
        
        $logMessage = sprintf(
            "[%s] %s in %s:%d\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        
        error_log($logMessage);
        
        if ($this->config['env']['debug'] ?? false) {
            $this->renderErrorPage($statusCode, $e->getMessage(), $e);
        } else {
            $this->renderErrorPage($statusCode, 'An error occurred');
        }
    }

    private function renderErrorPage(int $code, string $message, ?\Throwable $e = null): void
    {
        $errorFile = CMS_ROOT . "/themes/default/errors/{$code}.php";
        
        if (file_exists($errorFile)) {
            include $errorFile;
        } else {
            echo "<!DOCTYPE html>\n";
            echo "<html><head><title>Error {$code}</title></head><body>\n";
            echo "<h1>Error {$code}</h1>\n";
            echo "<p>" . htmlspecialchars($message) . "</p>\n";
            if ($e && ($this->config['env']['debug'] ?? false)) {
                echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
            }
            echo "</body></html>";
        }
    }
}

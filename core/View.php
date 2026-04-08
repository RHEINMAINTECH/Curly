<?php
/**
 * View Renderer
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

class View
{
    private string $viewsPath;
    private string $themePath;
    private array $shared = [];
    private array $sections = [];
    private string $currentSection = '';
    private ?string $layout = null;

    public function __construct(?string $viewsPath = null)
    {
        $this->viewsPath = $viewsPath ?? CMS_ROOT . '/app/Views';
        $this->themePath = CMS_ROOT . '/themes/default';
    }

    public function render(string $view, array $data = []): string
    {
        $file = $this->resolveView($view);
        
        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: {$view} ({$file})");
        }
        
        $data = array_merge($this->shared, $data);
        $data['__view'] = $this;
        
        return $this->capture($file, $data);
    }

    public function exists(string $view): bool
    {
        return file_exists($this->resolveView($view));
    }

    private function resolveView(string $view): string
    {
        // Check for theme view first
        $themeFile = $this->themePath . '/' . str_replace('.', '/', $view) . '.php';
        if (file_exists($themeFile)) {
            return $themeFile;
        }
        
        // Fall back to app views
        return $this->viewsPath . '/' . str_replace('.', '/', $view) . '.php';
    }

    private function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        
        ob_start();
        
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        
        $content = ob_get_clean();
        
        // Process layout if set
        if ($this->layout !== null) {
            $layoutFile = $this->resolveView($this->layout);
            $this->layout = null;
            $sections = $this->sections;
            $this->sections = [];
            
            $data['content'] = $content;
            $data['sections'] = $sections;
            
            return $this->capture($layoutFile, $data);
        }
        
        return $content;
    }

    public function share(string $key, $value): void
    {
        $this->shared[$key] = $value;
    }

    public function layout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function section(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    public function start(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function stop(): void
    {
        if ($this->currentSection !== '') {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = '';
        }
    }

    public function include(string $view, array $data = []): void
    {
        echo $this->render($view, $data);
    }

    public function escape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public function e($value): string
    {
        return $this->escape($value);
    }

    public function raw($value): string
    {
        return (string) $value;
    }

    public function csrf(): string
    {
        $token = App::getInstance()->getSession()->getToken();
        return '<input type="hidden" name="_token" value="' . $this->escape($token) . '">';
    }

    public function method(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . $this->escape($method) . '">';
    }

    public function old(string $key, $default = ''): string
    {
        $session = App::getInstance()->getSession();
        $old = $session->getFlash('old', []);
        return $this->escape($old[$key] ?? $default);
    }

    public function error(string $key): string
    {
        $session = App::getInstance()->getSession();
        $errors = $session->getFlash('errors', []);
        
        if (isset($errors[$key])) {
            return '<span class="error-message">' . $this->escape($errors[$key]) . '</span>';
        }
        
        return '';
    }

    public function asset(string $path): string
    {
        return Helper::asset($path);
    }

    public function url(string $path = ''): string
    {
        return Helper::url($path);
    }

    public function config(string $key, $default = null)
    {
        return App::getInstance()->getConfig($key, $default);
    }

    public function hasFlash(string $key): bool
    {
        return App::getInstance()->getSession()->hasFlash($key);
    }

    public function flash(string $key, $default = null)
    {
        return App::getInstance()->getSession()->getFlash($key, $default);
    }
}

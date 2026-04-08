<?php
/**
 * Base Controller
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

abstract class BaseController
{
    protected View $view;
    protected Database $db;
    protected Session $session;
    protected Security $security;
    protected ?AIService $ai;
    protected array $config;

    public function __construct()
    {
        $app = App::getInstance();
        
        $this->view = new View();
        $this->db = $app->getDatabase();
        $this->session = $app->getSession();
        $this->security = $app->getSecurity();
        $this->ai = $app->getAI();
        $this->config = $app->getConfig();
        
        $this->init();
    }

    protected function init(): void
    {
        // Override in child controllers
    }

    protected function render(string $view, array $data = []): void
    {
        echo $this->view->render($view, $data);
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function redirect(string $url, int $status = 302): void
    {
        Helper::redirect($url, $status);
    }

    protected function redirectBack(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    protected function redirectRoute(string $name, array $params = []): void
    {
        // TODO: Implement named routes
        $this->redirect('/');
    }

    protected function input(string $key = null, $default = null)
    {
        if ($key === null) {
            return array_merge($_GET, $_POST);
        }
        
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function validate(array $rules): array
    {
        $errors = [];
        $validated = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $this->input($field);
            
            foreach (explode('|', $fieldRules) as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleParam = $ruleParts[1] ?? null;
                
                switch ($ruleName) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field] = ucfirst($field) . ' is required.';
                        }
                        break;
                        
                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = ucfirst($field) . ' must be a valid email.';
                        }
                        break;
                        
                    case 'min':
                        if (!empty($value) && strlen($value) < (int) $ruleParam) {
                            $errors[$field] = ucfirst($field) . " must be at least {$ruleParam} characters.";
                        }
                        break;
                        
                    case 'max':
                        if (!empty($value) && strlen($value) > (int) $ruleParam) {
                            $errors[$field] = ucfirst($field) . " must not exceed {$ruleParam} characters.";
                        }
                        break;
                        
                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field] = ucfirst($field) . ' must be a number.';
                        }
                        break;
                        
                    case 'url':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$field] = ucfirst($field) . ' must be a valid URL.';
                        }
                        break;
                }
            }
            
            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }
        
        return ['valid' => empty($errors), 'errors' => $errors, 'data' => $validated];
    }

    protected function withError(string $key, string $message): void
    {
        $errors = $this->session->getFlash('errors', []);
        $errors[$key] = $message;
        $this->session->flash('errors', $errors);
    }

    protected function withErrors(array $errors): void
    {
        $this->session->flash('errors', $errors);
    }

    protected function withInput(): void
    {
        $this->session->flash('old', $this->input());
    }

    protected function withMessage(string $key, string $message): void
    {
        $this->session->flash($key, $message);
    }

    protected function isAuthenticated(): bool
    {
        return $this->session->has('user_id');
    }

    protected function user(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return $this->db->fetch(
            "SELECT * FROM users WHERE id = ?",
            [$this->session->get('user_id')]
        );
    }

    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->session->flash('error', 'Please log in to continue.');
            $this->redirect('/admin/login');
        }
    }

    protected function requireRole(string $role): void
    {
        $this->requireAuth();
        
        $user = $this->user();
        
        if (!$user || $user['role'] !== $role) {
            http_response_code(403);
            die('Access denied.');
        }
    }

    protected function can(string $permission): bool
    {
        $user = $this->user();
        
        if (!$user) {
            return false;
        }
        
        if ($user['role'] === 'admin') {
            return true;
        }
        
        // Check user permissions
        $result = $this->db->fetch(
            "SELECT 1 FROM user_permissions up
             JOIN permissions p ON up.permission_id = p.id
             WHERE up.user_id = ? AND p.name = ?",
            [$user['id'], $permission]
        );
        
        return $result !== null;
    }
}

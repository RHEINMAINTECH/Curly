<?php
/**
 * Session Handler
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

class Session
{
    private array $config;
    private static array $flash = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'name' => 'curlycms_session',
            'lifetime' => 7200,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ], $config);
    }

    public function start(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        
        // Configure session
        ini_set('session.cookie_lifetime', (string) $this->config['lifetime']);
        ini_set('session.gc_maxlifetime', (string) $this->config['lifetime']);
        
        session_set_cookie_params([
            'lifetime' => $this->config['lifetime'],
            'path' => $this->config['path'],
            'domain' => $this->config['domain'],
            'secure' => $this->config['secure'],
            'httponly' => $this->config['httponly'],
            'samesite' => $this->config['samesite']
        ]);
        
        session_name($this->config['name']);
        
        if (!session_start()) {
            return false;
        }
        
        // Initialize flash data from previous request
        if (isset($_SESSION['_flash'])) {
            self::$flash = $_SESSION['_flash'];
            unset($_SESSION['_flash']);
        }
        
        // Regenerate ID periodically to prevent session fixation
        if (!isset($_SESSION['_last_regeneration'])) {
            $_SESSION['_last_regeneration'] = time();
        } elseif (time() - $_SESSION['_last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['_last_regeneration'] = time();
        }
        
        return true;
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function all(): array
    {
        return $_SESSION ?? [];
    }

    public function flash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, $default = null)
    {
        return self::$flash[$key] ?? $default;
    }

    public function hasFlash(string $key): bool
    {
        return isset(self::$flash[$key]);
    }

    public function getToken(): string
    {
        if (!$this->has('_token')) {
            $this->set('_token', bin2hex(random_bytes(32)));
        }
        
        return $this->get('_token');
    }

    public function validateToken(string $token): bool
    {
        return hash_equals($this->getToken(), $token);
    }

    public function destroy(): bool
    {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        return session_destroy();
    }

    public function getId(): string
    {
        return session_id();
    }

    public function regenerateId(): bool
    {
        return session_regenerate_id(true);
    }
}

<?php
/**
 * Security Utilities
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

class Security
{
    private array $config;
    private ?string $salt = null;
    private ?string $cipher = null;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'salt' => getenv('SECURITY_SALT') ?: 'change-this-in-production',
            'cipher' => getenv('SECURITY_CIPHER') ?: 'aes-256-gcm',
            'csrf_enabled' => true
        ], $config);
        
        $this->salt = $this->config['salt'];
        $this->cipher = $this->config['cipher'];
    }

    public function validateCSRF(): void
    {
        if (!$this->config['csrf_enabled']) {
            return;
        }
        
        $session = App::getInstance()->getSession();
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!$token || !$session->validateToken($token)) {
            http_response_code(419);
            die('CSRF token mismatch. Please refresh and try again.');
        }
    }

    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    public function verifyHash(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function encrypt(string $data, ?string $key = null): string
    {
        $key = $key ?? $this->salt;
        $key = substr(hash('sha256', $key, true), 0, 32);
        
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $tag = '';
        $encrypted = openssl_encrypt($data, $this->cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        return base64_encode($iv . $tag . $encrypted);
    }

    public function decrypt(string $data, ?string $key = null): ?string
    {
        $key = $key ?? $this->salt;
        $key = substr(hash('sha256', $key, true), 0, 32);
        
        $data = base64_decode($data);
        
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $tagLength = 16;
        
        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, $tagLength);
        $encrypted = substr($data, $ivLength + $tagLength);
        
        $decrypted = openssl_decrypt($encrypted, $this->cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        return $decrypted !== false ? $decrypted : null;
    }

    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    public function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function sanitizeInput(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function sanitizeArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $key = $this->sanitizeInput($key);
            
            if (is_array($value)) {
                $result[$key] = $this->sanitizeArray($value);
            } else {
                $result[$key] = $this->sanitizeInput((string) $value);
            }
        }
        return $result;
    }

    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function createSignature(array $data): string
    {
        ksort($data);
        $string = json_encode($data) . $this->salt;
        return hash_hmac('sha256', $string, $this->salt);
    }

    public function verifySignature(array $data, string $signature): bool
    {
        $expected = $this->createSignature($data);
        return hash_equals($expected, $signature);
    }

    public function rateLimit(string $key, int $maxAttempts = 60, int $decayMinutes = 1): bool
    {
        $cache = App::getInstance()->getCache();
        $cacheKey = "rate_limit:{$key}";
        
        $attempts = (int) $cache->get($cacheKey, 0);
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        $cache->set($cacheKey, $attempts + 1, $decayMinutes * 60);
        return true;
    }

    public function clearRateLimit(string $key): void
    {
        $cache = App::getInstance()->getCache();
        $cache->delete("rate_limit:{$key}");
    }
}

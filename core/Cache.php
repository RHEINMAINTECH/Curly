<?php
/**
 * Cache Handler
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

class Cache
{
    private string $driver;
    private string $path;
    private int $defaultTtl;
    private array $memory = [];

    public function __construct(array $config = [])
    {
        $this->driver = $config['driver'] ?? getenv('CACHE_DRIVER') ?? 'file';
        $this->path = $config['path'] ?? CMS_STORAGE . '/cache';
        $this->defaultTtl = (int) ($config['lifetime'] ?? getenv('CACHE_LIFETIME') ?? 3600);
        
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key, $default = null)
    {
        // Check memory cache first
        if (isset($this->memory[$key])) {
            return $this->memory[$key]['value'];
        }
        
        if ($this->driver === 'file') {
            return $this->getFromFile($key, $default);
        }
        
        // TODO: Implement Redis/Memcached drivers
        
        return $default;
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        // Store in memory
        $this->memory[$key] = ['value' => $value, 'ttl' => $ttl ?? $this->defaultTtl];
        
        if ($this->driver === 'file') {
            return $this->setToFile($key, $value, $ttl);
        }
        
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->memory[$key]);
        
        if ($this->driver === 'file') {
            $file = $this->getCacheFile($key);
            if (file_exists($file)) {
                return unlink($file);
            }
        }
        
        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function clear(): bool
    {
        $this->memory = [];
        
        if ($this->driver === 'file') {
            $files = glob($this->path . '/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
        }
        
        return true;
    }

    public function remember(string $key, int $ttl, callable $callback)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    public function forget(string $key): bool
    {
        return $this->delete($key);
    }

    private function getCacheFile(string $key): string
    {
        $hash = md5($key);
        return $this->path . '/' . $hash . '.cache';
    }

    private function getFromFile(string $key, $default)
    {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $content = file_get_contents($file);
        $data = unserialize($content);
        
        if ($data === false) {
            return $default;
        }
        
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            unlink($file);
            return $default;
        }
        
        return $data['value'];
    }

    private function setToFile(string $key, $value, ?int $ttl): bool
    {
        $file = $this->getCacheFile($key);
        $ttl = $ttl ?? $this->defaultTtl;
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }

    public function increment(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $value;
        $this->set($key, $new);
        return $new;
    }

    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, -$value);
    }
}

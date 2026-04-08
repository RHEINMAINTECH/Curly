<?php
/**
 * Helper Functions
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

class Helper
{
    public static function loadEnv(string $file): void
    {
        if (!file_exists($file)) {
            return;
        }
        
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') === false) {
                continue;
            }
            
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes
            if (strlen($value) > 1 && 
                (($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                 ($value[0] === "'" && $value[strlen($value) - 1] === "'"))) {
                $value = substr($value, 1, -1);
            }
            
            // Set environment variable
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }

    public static function slug(string $text, string $separator = '-'): string
    {
        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');
        
        // Replace non-alphanumeric characters
        $text = preg_replace('/[^a-z0-9]+/i', $separator, $text);
        
        // Remove duplicate separators
        $text = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $text);
        
        // Trim separators
        $text = trim($text, $separator);
        
        return $text;
    }

    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }

    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public static function formatDate($date, string $format = 'Y-m-d H:i:s'): string
    {
        if (is_string($date)) {
            $date = strtotime($date);
        }
        
        return date($format, $date);
    }

    public static function timeAgo($date): string
    {
        if (is_string($date)) {
            $date = strtotime($date);
        }
        
        $diff = time() - $date;
        
        $intervals = [
            'year' => 31536000,
            'month' => 2592000,
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60
        ];
        
        foreach ($intervals as $unit => $seconds) {
            $value = floor($diff / $seconds);
            
            if ($value >= 1) {
                $plural = $value > 1 ? 's' : '';
                return "{$value} {$unit}{$plural} ago";
            }
        }
        
        return 'just now';
    }

    public static function jsonEncode($data, int $options = 0): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | $options);
    }

    public static function jsonDecode(string $json, bool $assoc = true)
    {
        return json_decode($json, $assoc);
    }

    public static function arrayGet(array $array, string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    public static function arraySet(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        
        $current = $value;
    }

    public static function randomString(int $length = 16): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = '';
        
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $str;
    }

    public static function redirect(string $url, int $code = 302): void
    {
        header("Location: {$url}", true, $code);
        exit;
    }

    public static function asset(string $path): string
    {
        $baseUrl = App::getInstance()->getConfig('env.url', '');
        return rtrim($baseUrl, '/') . '/public/' . ltrim($path, '/');
    }

    public static function url(string $path = ''): string
    {
        $baseUrl = App::getInstance()->getConfig('env.url', '');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    public static function isActive(string $path): bool
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return strpos($uri, $path) === 0;
    }

    public static function excerpt(string $html, int $length = 150): string
    {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);
        return self::truncate(trim($text), $length);
    }
}

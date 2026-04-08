<?php
/**
 * Extension Sandbox - Secure Execution Environment
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

class Sandbox
{
    private array $allowedFunctions = [];
    private array $allowedClasses = [];
    private array $forbiddenFunctions = [
        'exec', 'shell_exec', 'system', 'passthru', 'popen',
        'proc_open', 'pcntl_exec', 'eval', 'assert',
        'file_put_contents', 'fwrite', 'fputs',
        'move_uploaded_file', 'copy', 'rename',
        'unlink', 'rmdir', 'mkdir',
        'chmod', 'chown', 'chgrp',
        'symlink', 'link',
        'apache_setenv', 'putenv',
        'mail', 'header', 'header_remove',
        'setcookie', 'setrawcookie',
        'session_start', 'session_destroy', 'session_regenerate_id',
        'mysql_query', 'mysqli_query', 'pg_query',
        'sqlite_query', 'sqlite_exec'
    ];
    
    private array $allowedDirectories = [];
    private string $extensionPath;
    private array $hooks = [];
    private array $extensionInfo = [];

    public function __construct(string $extensionPath)
    {
        $this->extensionPath = $extensionPath;
        $this->allowedDirectories = [
            CMS_STORAGE . '/uploads',
            CMS_STORAGE . '/cache',
            CMS_ROOT . '/public/uploads'
        ];
        
        $this->initAllowedFunctions();
        $this->initAllowedClasses();
    }

    private function initAllowedFunctions(): void
    {
        $this->allowedFunctions = [
            // String functions
            'strlen', 'strpos', 'strrpos', 'substr', 'str_replace',
            'str_ireplace', 'strtolower', 'strtoupper', 'ucfirst',
            'lcfirst', 'ucwords', 'trim', 'ltrim', 'rtrim',
            'explode', 'implode', 'join', 'split', 'str_split',
            'preg_match', 'preg_match_all', 'preg_replace', 'preg_split',
            'sprintf', 'printf', 'vsprintf', 'vprintf',
            'htmlspecialchars', 'htmlentities', 'strip_tags',
            'nl2br', 'wordwrap', 'str_pad', 'str_repeat',
            
            // Array functions
            'array', 'array_push', 'array_pop', 'array_shift', 'array_unshift',
            'array_merge', 'array_merge_recursive', 'array_combine',
            'array_keys', 'array_values', 'array_flip', 'array_reverse',
            'array_search', 'array_key_exists', 'in_array',
            'array_map', 'array_filter', 'array_reduce', 'array_walk',
            'array_slice', 'array_splice', 'array_chunk', 'array_fill',
            'count', 'sizeof', 'reset', 'end', 'current', 'key', 'next', 'prev',
            'sort', 'rsort', 'asort', 'arsort', 'ksort', 'krsort',
            'usort', 'uasort', 'uksort',
            
            // Math functions
            'abs', 'ceil', 'floor', 'round', 'max', 'min',
            'rand', 'mt_rand', 'random_int', 'random_bytes',
            'sqrt', 'pow', 'exp', 'log', 'log10',
            'sin', 'cos', 'tan', 'asin', 'acos', 'atan',
            
            // Date functions
            'date', 'time', 'strtotime', 'mktime', 'checkdate',
            'strftime', 'gmdate', 'getdate', 'localtime',
            
            // JSON
            'json_encode', 'json_decode',
            
            // File read-only
            'file_exists', 'is_file', 'is_dir', 'is_readable',
            'file_get_contents', 'file', 'filetype', 'filesize',
            'filemtime', 'fileatime', 'basename', 'dirname',
            'pathinfo', 'realpath', 'is_writable', 'is_writeable',
            
            // URL/Network
            'parse_url', 'parse_str', 'http_build_query',
            'urlencode', 'urldecode', 'rawurlencode', 'rawurldecode',
            'base64_encode', 'base64_decode',
            
            // Type checking
            'is_array', 'is_bool', 'is_float', 'is_int', 'is_integer',
            'is_null', 'is_numeric', 'is_object', 'is_string', 'is_callable',
            'gettype', 'settype', 'empty', 'isset', 'unset',
            'boolval', 'intval', 'floatval', 'strval',
            
            // Variable handling
            'var_dump', 'var_export', 'print_r', 'serialize', 'unserialize',
            'get_defined_vars', 'get_defined_functions',
            
            // Misc
            'die', 'exit', 'sleep', 'usleep',
            'class_exists', 'interface_exists', 'trait_exists', 'method_exists',
            'property_exists', 'get_class', 'get_class_methods', 'get_class_vars',
            'get_object_vars', 'call_user_func', 'call_user_func_array'
        ];
    }

    private function initAllowedClasses(): void
    {
        $this->allowedClasses = [
            // CMS Core classes (limited access)
            \CurlyCMS\Core\Helper::class,
            \CurlyCMS\Core\View::class,
            
            // Standard PHP classes
            \DateTime::class,
            \DateTimeZone::class,
            \DateInterval::class,
            \DatePeriod::class,
            \Exception::class,
            \InvalidArgumentException::class,
            \RuntimeException::class,
            \ArrayIterator::class,
            \ArrayObject::class,
            \stdClass::class,
            \Closure::class,
            \SplFileInfo::class,
            \SplFileObject::class,
            \DirectoryIterator::class,
            \FilesystemIterator::class,
            \RecursiveDirectoryIterator::class,
            \RecursiveIteratorIterator::class,
            \FilterIterator::class,
            \IteratorIterator::class,
            \LimitIterator::class
        ];
    }

    public function loadExtension(string $name): bool
    {
        $manifestFile = $this->extensionPath . '/' . $name . '/manifest.json';
        
        if (!file_exists($manifestFile)) {
            return false;
        }
        
        $manifest = json_decode(file_get_contents($manifestFile), true);
        
        if (!$manifest) {
            return false;
        }
        
        $this->extensionInfo[$name] = $manifest;
        
        // Load extension permissions from manifest
        if (isset($manifest['permissions'])) {
            $this->applyPermissions($manifest['permissions']);
        }
        
        return true;
    }

    private function applyPermissions(array $permissions): void
    {
        if (isset($permissions['functions'])) {
            $this->allowedFunctions = array_merge(
                $this->allowedFunctions,
                $permissions['functions']
            );
        }
        
        if (isset($permissions['classes'])) {
            $this->allowedClasses = array_merge(
                $this->allowedClasses,
                $permissions['classes']
            );
        }
    }

    public function execute(string $code, array $context = [])
    {
        $this->validateCode($code);
        
        // Create a restricted execution environment
        $sandbox = $this;
        $allowedFunctions = $this->allowedFunctions;
        $forbiddenFunctions = $this->forbiddenFunctions;
        
        // Create wrapper functions
        $context['cms'] = new SandboxAPI($this);
        $context['_sandbox'] = $sandbox;
        
        // Extract context variables
        extract($context, EXTR_SKIP);
        
        // Execute in isolated scope
        try {
            $result = eval($code);
            return $result;
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Sandbox execution error: " . $e->getMessage()
            );
        }
    }

    private function validateCode(string $code): void
    {
        // Check for forbidden patterns
        $forbiddenPatterns = [
            '/\beval\s*\(/i',
            '/\bassert\s*\(/i',
            '/\bcreate_function\s*\(/i',
            '/\bpreg_replace\s*\([^)]*\/[a-z]*e[a-z]*[,\)]/i',
            '/\bfile_put_contents\s*\(/i',
            '/\bfwrite\s*\(/i',
            '/\bexec\s*\(/i',
            '/\bshell_exec\s*\(/i',
            '/\bsystem\s*\(/i',
            '/\bpassthru\s*\(/i',
            '/\b`/',
            '/\binclude\s*\$/i',
            '/\brequire\s*\$/i',
            '/\binclude_once\s*\$/i',
            '/\brequire_once\s*\$/i',
        ];
        
        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $code)) {
                throw new \RuntimeException(
                    "Forbidden pattern detected in sandbox code"
                );
            }
        }
        
        // Check for namespace manipulation
        if (preg_match('/\bnamespace\s+/i', $code)) {
            throw new \RuntimeException(
                "Namespace manipulation not allowed in sandbox"
            );
        }
    }

    public function executeFile(string $file, array $context = [])
    {
        if (!file_exists($file)) {
            throw new \RuntimeException("File not found: {$file}");
        }
        
        // Check if file is within allowed directories
        $realPath = realpath($file);
        $extensionRealPath = realpath($this->extensionPath);
        
        if (strpos($realPath, $extensionRealPath) !== 0) {
            throw new \RuntimeException(
                "File execution denied: outside extension directory"
            );
        }
        
        $code = file_get_contents($file);
        
        // Remove PHP tags for eval
        $code = preg_replace('/^<\?php/', '', $code);
        $code = preg_replace('/\?>$/', '', $code);
        
        return $this->execute($code, $context);
    }

    public function registerHook(string $hook, callable $callback): void
    {
        $this->hooks[$hook][] = $callback;
    }

    public function triggerHook(string $hook, array $data = []): array
    {
        $results = [];
        
        if (isset($this->hooks[$hook])) {
            foreach ($this->hooks[$hook] as $callback) {
                try {
                    $results[] = call_user_func($callback, $data);
                } catch (\Throwable $e) {
                    error_log("Hook error [{$hook}]: " . $e->getMessage());
                }
            }
        }
        
        return $results;
    }

    public function isAllowedFunction(string $function): bool
    {
        return in_array($function, $this->allowedFunctions) &&
               !in_array($function, $this->forbiddenFunctions);
    }

    public function isAllowedClass(string $class): bool
    {
        return in_array($class, $this->allowedClasses);
    }

    public function isAllowedPath(string $path): bool
    {
        $realPath = realpath($path);
        
        if ($realPath === false) {
            return false;
        }
        
        foreach ($this->allowedDirectories as $allowedDir) {
            if (strpos($realPath, realpath($allowedDir)) === 0) {
                return true;
            }
        }
        
        return false;
    }

    public function getExtensionInfo(string $name): ?array
    {
        return $this->extensionInfo[$name] ?? null;
    }
}
